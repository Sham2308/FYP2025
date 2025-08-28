<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Borrow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BorrowController extends Controller
{
    /**
     * Show borrow form + recent borrows
     */
    public function index()
    {
        $borrows = Borrow::with('item')->latest()->take(10)->get();
        return view('borrow.index', compact('borrows'));
    }

    /**
     * Store a new borrow record + mirror to Google Sheets via Apps Script (no extra library)
     */
    public function store(Request $request)
    {
        $request->validate([
            'uid' => 'required|string',
            'user_id' => 'required|string', // accept staff/student IDs
        ]);

        $item = Item::where('uid', $request->uid)->first();

        if (!$item) {
            return redirect()->back()->with('error', 'Item with this UID not found.');
        }

        if ($item->status !== 'available') {
            return redirect()->back()->with('error', 'Item is not available for borrowing.');
        }

        $borrow = Borrow::create([
            'uid' => $request->uid,
            'user_id' => $request->user_id,
            'borrowed_at' => now(),
        ]);

        $item->update(['status' => 'borrowed']);

        // Mirror to Google Sheets (Apps Script Web App)
        $this->mirrorToSheet([
            'type'       => 'borrow',
            'borrow_id'  => $borrow->id,
            'user_id'    => $borrow->user_id,
            'uid'        => $borrow->uid,
            'asset_id'   => optional($item)->asset_id,
            'serial_no'  => optional($item)->serial_no,
            'action'     => 'borrowed',
        ]);

        return redirect()->back()->with('success', 'Borrow saved successfully!');
    }

    /**
     * Return a borrowed item + mirror to Google Sheets via Apps Script (no extra library)
     */
    public function returnItem(Request $request, $uid)
    {
        $item = Item::where('uid', $uid)->first();

        if (!$item) {
            return redirect()->back()->with('error', 'Item not found.');
        }

        $borrow = Borrow::where('uid', $uid)
            ->whereNull('returned_at')
            ->latest()
            ->first();

        if (!$borrow) {
            return redirect()->back()->with('error', 'No active borrow record found for this item.');
        }

        $borrow->update(['returned_at' => now()]);
        $item->update(['status' => 'available']);

        // Mirror to Google Sheets (Apps Script Web App)
        $this->mirrorToSheet([
            'type'       => 'borrow',
            'borrow_id'  => $borrow->id,
            'user_id'    => $borrow->user_id,
            'uid'        => $borrow->uid,
            'asset_id'   => optional($item)->asset_id,
            'serial_no'  => optional($item)->serial_no,
            'action'     => 'returned',
        ]);

        return redirect()->back()->with('success', 'Item returned successfully!');
    }

    /**
     * Send a row to Google Sheets via Apps Script Web App.
     * Uses:
     *  - GOOGLE_SHEET_WEBAPP_URL
     *  - GOOGLE_SHEET_WEBAPP_SECRET
     */
    protected function mirrorToSheet(array $payload): void
    {
        $url = env('GOOGLE_SHEET_WEBAPP_URL');
        $secret = env('GOOGLE_SHEET_WEBAPP_SECRET');

        if (!$url || !$secret) {
            Log::warning('Sheet mirror skipped: missing GOOGLE_SHEET_WEBAPP_URL or GOOGLE_SHEET_WEBAPP_SECRET');
            return;
        }

        try {
            Http::timeout(10)->asJson()->post($url, array_merge($payload, [
                'secret' => $secret,
            ]));
        } catch (\Throwable $e) {
            // Don’t block the user flow if Sheets fails—just log it.
            Log::warning('Sheet mirror failed: ' . $e->getMessage());
        }
    }
}
