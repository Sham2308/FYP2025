<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Borrow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BorrowController extends Controller
{
    public function index()
    {
        $borrows = Borrow::with('item')->latest()->take(10)->get();
        $items = Item::orderBy('asset_id')->get();
        return view('borrow.index', compact('borrows', 'items'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'card_uid'      => 'required|string',
            'user_id'       => 'required|string',
            'borrower_name' => 'nullable|string|max:191',
            'borrow_date'   => 'nullable|date',
            'due_date'      => 'nullable|date',
            'remarks'       => 'nullable|string',
            'items'         => 'required|array',
            'items.*.uid'   => 'required|string',
        ]);

        foreach ($request->items as $itemData) {
            $item = Item::where('uid', $itemData['uid'])->first();

            if (!$item) {
                continue; // skip missing item
            }

            if ($item->status !== 'available') {
                continue; // skip unavailable
            }

            $borrow = Borrow::create([
                'uid'           => $item->uid,
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
                'timestamp'     => now()->format('Y-m-d H:i:s'),
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
                'status'        => 'borrowed',
                'remarks'       => $borrow->remarks,
            ]);
        }

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
            'timestamp'     => now()->format('Y-m-d H:i:s'),
            'borrow_id'     => $borrow->id,
            'user_id'       => $borrow->user_id,
            'borrower_name' => $borrow->borrower_name,
            'uid'           => $borrow->uid,
            'asset_id'      => optional($item)->asset_id,
            'name'          => optional($item)->name,
            'borrow_date'   => optional($borrow->borrow_date)->format('Y-m-d'),
            'return_date'   => optional($borrow->due_date)->format('Y-m-d'),
            'borrowed_at'   => optional($borrow->borrowed_at)->format('Y-m-d'),
            'returned_at'   => now()->format('Y-m-d'),
            'status'        => 'available',
            'remarks'       => $borrow->remarks,
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
            'uid'           => $item->uid,
            'asset_id'      => $item->asset_id,
            'name'          => $item->name,
            'detail'        => $item->detail,
            'accessories'   => $item->accessories,
            'type_id'       => $item->type_id,
            'serial_no'     => $item->serial_no,
            'status'        => $item->status,
            'purchase_date' => $item->purchase_date,
            'remarks'       => $item->remarks,
        ]);
    }

    public function getUserByUid($cardUid)
    {
        $service = new \App\Services\GoogleSheetService();
        $response = $service->getValues('Users!A:C');
        $values = $response->getValues();

        $studentId = null;
        $name = null;

        foreach ($values as $index => $row) {
            if ($index === 0) continue;
            if (isset($row[0]) && trim($row[0]) === trim($cardUid)) {
                $studentId = $row[1] ?? null;
                $name      = $row[2] ?? null;
                break;
            }
        }

        if (!$studentId || !$name) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json([
            'uid'        => $cardUid,
            'student_id' => $studentId,
            'name'       => $name
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
