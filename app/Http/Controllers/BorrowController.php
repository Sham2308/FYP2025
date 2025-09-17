<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Borrow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BorrowController extends Controller
{
    public function __construct()
    {
        // Guests can view the borrow page; actions require auth
        $this->middleware('auth')->except(['index']);
    }

    public function index()
    {
        $borrows = Borrow::with('item')->latest()->take(10)->get();
        $items = Item::orderBy('asset_id')->get();
        return view('borrow.index', compact('borrows', 'items'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'uid'           => 'required|string',
            'user_id'       => 'required|string',
            'borrower_name' => 'nullable|string|max:191',
            'borrow_date'   => 'nullable|date',
            'due_date'      => 'nullable|date',
            'remarks'       => 'nullable|string',
        ]);

        $item = Item::where('uid', $request->uid)->first();

        if (!$item) {
            return redirect()->back()->with('error', 'Item with this UID not found.');
        }

        if ($item->status !== 'available') {
            return redirect()->back()->with('error', 'Item is not available for borrowing.');
        }

        $borrow = Borrow::create([
            'uid'           => $request->uid,
            'user_id'       => $request->user_id,
            'borrower_name' => $request->borrower_name,
            'borrow_date'   => $request->borrow_date ?? now()->toDateString(),
            'due_date'      => $request->due_date,
            'remarks'       => $request->remarks,
            'borrowed_at'   => now(),
        ]);

        $item->update(['status' => 'borrowed']);

        $this->mirrorToSheet([
            'type'          => 'borrow',
            'borrow_id'     => $borrow->id,
            'user_id'       => $borrow->user_id,
            'borrower_name' => $borrow->borrower_name,
            'uid'           => $borrow->uid,
            'asset_id'      => optional($item)->asset_id,
            'name'          => optional($item)->name,
            'borrow_date'   => optional($borrow->borrow_date)->format('Y-m-d'),
            'return_date'   => optional($borrow->due_date)->format('Y-m-d'),
            'borrowed_at'   => optional($borrow->borrowed_at)->format('Y-m-d'),
            'returned_at'   => '',
            'status'        => strtolower('borrowed'),
            'remarks'       => $borrow->remarks,
        ]);

        return redirect()->back()->with('success', 'Borrow saved successfully!');
    }

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
            return redirect()->back()->with('error', 'No active borrow record found.');
        }

        $borrow->update([
            'returned_at' => now(),
            'return_date' => now()->toDateString(),
        ]);
        $item->update(['status' => 'available']);

        $this->mirrorToSheet([
            'type'          => 'borrow',
            'borrow_id'     => $borrow->id,
            'user_id'       => $borrow->user_id,
            'borrower_name' => $borrow->borrower_name ?? '',
            'uid'           => $borrow->uid,
            'asset_id'      => optional($item)->asset_id,
            'name'          => optional($item)->name,
            'borrow_date'   => optional($borrow->borrow_date)->format('Y-m-d'),
            'return_date'   => optional($borrow->return_date)->format('Y-m-d'),
            'borrowed_at'   => optional($borrow->borrowed_at)->format('Y-m-d'),
            'returned_at'   => optional($borrow->returned_at)->format('Y-m-d'),
            'status'        => strtolower($item->status),
            'remarks'       => $borrow->remarks ?? '',
        ]);

        return redirect()->back()->with('success', 'Item returned successfully!');
    }

    public function destroy($id)
    {
        $borrow = Borrow::findOrFail($id);
        $item = Item::where('uid', $borrow->uid)->first();

        if ($item) {
            $item->update(['status' => 'available']);
        }

        $this->mirrorToSheet([
            'type'       => 'delete',
            'borrow_id'  => $borrow->id,
            'uid'        => $borrow->uid,
        ]);

        $borrow->delete();

        return redirect()->back()->with('success', 'Borrow record deleted successfully!');
    }

    public function fetchItem($uid)
    {
        $item = Item::where('uid', $uid)->first();

        if (!$item) {
            return response()->json(['error' => 'Item not found'], 404);
        }

        return response()->json([
            'asset_id'      => $item->asset_id,
            'name'          => $item->name,
            'status'        => $item->status,
            'purchase_date' => $item->purchase_date,
        ]);
    }

    protected function mirrorToSheet(array $payload): void
    {
        $url = config('services.google.webapp_url');
        $secret = config('services.google.secret');

        if (!$url || !$secret) {
            Log::warning('Sheet mirror skipped: missing credentials');
            return;
        }

        try {
            Http::timeout(10)->asJson()->post($url, array_merge($payload, [
                'secret' => $secret,
            ]));
        } catch (\Throwable $e) {
            Log::warning('Sheet mirror failed: ' . $e->getMessage());
        }
    }
}
