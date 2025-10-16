<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

class BorrowController extends Controller
{
    public function index()
{
    try {
        $gs = app(\App\Services\GoogleSheetService::class);

        // ✅ Try to fetch data directly from Google Sheet
        if ($gs && $gs->isReady()) {
            $values = $gs->getValues('Items!A:I')->getValues(); // match your sheet layout
            if ($values && count($values) > 1) {
                $rows = array_slice($values, 1); // skip header
                $items = [];

                foreach ($rows as $r) {
                    $items[] = (object) [
                        'ItemID'          => $r[0] ?? '',
                        'asset_id'     => $r[1] ?? '',
                        'name'         => $r[2] ?? '',
                        'detail'       => $r[3] ?? '',
                        'accessories'  => $r[4] ?? '',
                        'type_id'      => $r[5] ?? '',
                        'serial_no'    => $r[6] ?? '',
                        'status'       => strtolower(trim($r[7] ?? 'available')),
                        'purchase_date'=> $r[8] ?? '',
                    ];
                }

                // ✅ Also include recent borrow logs
                $recentFromSheets = $this->readRecentFromSheet();

                return view('borrow.index', [
                    'items'  => $items,
                    'recent' => $recentFromSheets,
                ]);
            }
        }

        // 🧩 Fallback to DB if Google Sheet not available
        $items = \App\Models\Item::orderBy('asset_id')->get();
        $recentFromSheets = $this->readRecentFromSheet();

        return view('borrow.index', [
            'items'  => $items,
            'recent' => $recentFromSheets,
            'error'  => '⚠️ Google Sheet not reachable, showing local DB data.'
        ]);

    } catch (\Throwable $e) {
        \Log::error('BorrowController@index failed: '.$e->getMessage());
        $items = \App\Models\Item::orderBy('asset_id')->get();
        $recentFromSheets = $this->readRecentFromSheet();

        return view('borrow.index', [
            'items'  => $items,
            'recent' => $recentFromSheets,
            'error'  => '⚠️ Error fetching Google Sheet data. Showing fallback DB data.'
        ]);
    }
}


    /**
     * 🔍 Fetch user info from Google Sheet by Card UID
     */
    public function getUserByUid($cardUid)
    {
        try {
            $gs = app(\App\Services\GoogleSheetService::class);
            if (!$gs->isReady()) {
                return response()->json(['error' => 'Google Sheets API not configured'], 500);
            }

            $values = $gs->getValues('Users!A:C')->getValues();
            if (!$values || count($values) <= 1) {
                return response()->json(['error' => 'No user data in sheet'], 404);
            }

            foreach ($values as $i => $row) {
                if ($i === 0) continue;
                if (isset($row[0]) && trim($row[0]) === trim($cardUid)) {
                    return response()->json([
                        'card_id'    => $cardUid,
                        'student_id' => $row[1] ?? '',
                        'name'       => $row[2] ?? '',
                    ]);
                }
            }

            return response()->json(['error' => 'User not found in Google Sheet'], 404);
        } catch (\Throwable $e) {
            Log::error('getUserByUid failed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch item by Item ID from Google Sheets
     */
    public function fetchItem($itemId)
    {
        try {
            $gs = app(\App\Services\GoogleSheetService::class);
            if (!$gs->isReady()) {
                return response()->json(['error' => 'Google Sheets API not configured'], 500);
            }

            $values = $gs->getValues('Items!A:E')->getValues();
            if (!$values || count($values) <= 1) {
                return response()->json(['error' => 'No items in Google Sheet'], 404);
            }

            foreach ($values as $i => $row) {
                if ($i === 0) continue;
                if (isset($row[0]) && trim($row[0]) === trim($itemId)) {
                    return response()->json([
                        'item_id'   => $row[0] ?? '',
                        'asset_id'  => $row[1] ?? '',
                        'name'      => $row[2] ?? '',
                        'status'    => $row[3] ?? '',
                        'purchased' => $row[4] ?? '',
                    ]);
                }
            }

            return response()->json(['error' => 'Item not found in Google Sheet'], 404);
        } catch (\Throwable $e) {
            Log::error('fetchItem failed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * ✅ Handle multiple-item borrow
     */
    public function publicStore(Request $request)
{
    $request->validate([
        'card_uid'      => 'required|string',
        'user_id'       => 'required|string',
        'borrower_name' => 'required|string',
        'borrow_date'   => 'nullable|date',
        'due_date'      => 'nullable|date',
        'remarks'       => 'nullable|string',
        'items'         => 'required|array',
        'items.*.item_id' => 'required|string',
    ]);

    try {
        $gs = app(\App\Services\GoogleSheetService::class);
        if (!$gs->isReady()) {
            return back()->with('error', 'Google Sheets API not configured.');
        }

        $newRows = [];

        foreach ($request->items as $item) {
            $row = [
                $request->card_uid,                  // Card ID
                $request->borrower_name,             // Borrower Name
                $request->user_id,                   // Student/Staff ID
                $item['item_id'] ?? '',              // Item ID
                $request->borrow_date ?? now()->toDateString(), // Borrow Date
                $request->due_date ?? '',            // Return Date
                now('Asia/Brunei')->format('g:i A'), // Borrowed At
                '',                                  // Returned At
                'borrowed',                          // Status
            ];

            // Append the borrow entry to BorrowDetails sheet
            $gs->appendRow($row, 'BorrowDetails!A:I');

            // Update the status in Items sheet to "borrowed"
            $this->updateItemStatus($item['item_id'], 'borrowed');
            \App\Models\Item::where('item_id', $item['item_id'])->update(['status' => 'borrowed']);

            $newRows[] = [
                'CardID'       => $row[0],
                'BorrowerName' => $row[1],
                'UserID'       => $row[2],
                'ItemID'       => $row[3],
                'BorrowDate'   => $row[4],
                'ReturnDate'   => $row[5],
                'BorrowedAt'   => $row[6],
                'ReturnedAt'   => $row[7],
                'Status'       => $row[8],
            ];
        }

        // ✉️ Send Borrow Confirmation Email
        try {
            $email = $request->email ?? null;
            $borrowerName = $request->borrower_name ?? 'Borrower';

            if ($email) {
                $email = strtolower(trim($email));

                Mail::send('emails.borrow_confirmation', [
                    'name' => $borrowerName,
                    'items' => $request->items,
                    'borrow_date' => $request->borrow_date,
                    'due_date' => $request->due_date,
                ], function ($m) use ($email) {
                    $m->from('noreply@tapnborrow.com', 'TapNBorrow');
                    $m->to($email);
                    $m->subject('TapNBorrow Borrow Confirmation');
                });

                Log::info("✅ Borrow email sent to {$email}");
            } else {
                Log::warning("⚠️ No email provided for borrow entry");
            }
        } catch (\Throwable $e) {
            Log::error('❌ Borrow email send failed: ' . $e->getMessage());
        }

        // ✅ Store session and return success
        $recent = session('recentBorrow', []);
        $recent = array_merge($newRows, $recent);
        session(['recentBorrow' => $recent]);

        return back()->with('success', '✅ All items borrowed and email sent successfully.');

    } catch (\Throwable $e) {
        Log::error('publicStore failed: ' . $e->getMessage());
        return back()->with('error', '❌ Failed: ' . $e->getMessage());
    }
}



    /**
     * ✅ Return item by Item ID
     */
    public function returnByUid(Request $request, string $itemId)
{
    try {
        $gs = app(\App\Services\GoogleSheetService::class);
        if (!$gs->isReady()) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Google Sheets API not configured.'], 500)
                : back()->with('error', 'Google Sheets API not configured.');
        }

        $last = $this->findLatestBorrowRowByUid($itemId);
        if (!$last) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'No borrow record found for this Item ID.'], 404)
                : back()->with('error', 'No borrow record found for this Item ID.');
        }

        $row = [
            $last['CardID'] ?? '',
            $last['BorrowerName'] ?? '',
            $last['UserID'] ?? '',
            $last['ItemID'] ?? '',
            $last['BorrowDate'] ?? '',
            $last['ReturnDate'] ?? '',
            $last['BorrowedAt'] ?? '',
            now('Asia/Brunei')->format('g:i A'),
            'available',
        ];

        // Append the return entry to BorrowDetails sheet
        $gs->appendRow($row, 'BorrowDetails!A:I');

        // Update the item status to "available" in the Items sheet
        $this->updateItemStatus($itemId, 'available');

        // Update the local database to "available" (if needed)
        \App\Models\Item::where('item_id', $itemId)->update(['status' => 'available']);

        // Refresh the recent borrow logs from Google Sheets
        $updated = $this->readRecentFromSheet();
        session(['recentBorrow' => $updated]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Marked returned and appended.',
                'item'    => $row,
            ]);
        }

        return back()->with('success', '✅ Marked returned and appended.');
    } catch (\Throwable $e) {
        \Log::error('returnByUid failed: ' . $e->getMessage());
        return $request->expectsJson()
            ? response()->json(['success' => false, 'message' => 'Failed to mark returned.'], 500)
            : back()->with('error', '❌ Failed to mark returned.');
    }
}


    protected function updateItemStatus(string $itemId, string $status): void
{
    try {
        $gs = app(\App\Services\GoogleSheetService::class);
        if (!$gs->isReady()) {
            \Log::warning('GoogleSheetService not ready.');
            return;
        }

        // Get data from Items sheet
        $values = $gs->getValues('Items!A:I')->getValues();
        if (!$values || count($values) <= 1) {
            \Log::warning('No data found in Items sheet.');
            return;
        }

        // Capitalize status correctly (ensure consistency with your sheet)
        $sheetStatus = ucfirst(strtolower(trim($status))); // Example: "available" -> "Available"
        \Log::info("Attempting to update status: {$itemId} → {$sheetStatus}");

        foreach ($values as $i => $row) {
            if ($i === 0) continue; // skip header

            // Assuming ItemID is in column B (adjust if necessary)
            $sheetItemId = trim($row[1] ?? ''); // column B = asset_id

            if ($sheetItemId === $itemId) {
                $rowNum = $i + 1; // row number for the Google Sheets API
                $cell = "H{$rowNum}"; // column H = status column in your sheet

                // Update the status in the Items sheet
                $ok = $gs->updateRow('Items', $cell, [$sheetStatus]);

                if ($ok) {
                    \Log::info("✅ Updated Item {$itemId} status to {$sheetStatus} (row {$rowNum})");
                } else {
                    \Log::warning("⚠️ Failed to update {$itemId} at {$cell}");
                }
                break;
            }
        }
    } catch (\Throwable $e) {
        \Log::error('updateItemStatus failed: ' . $e->getMessage());
    }
}


    protected function readRecentFromSheet(): array
{
    try {
        $gs = app(\App\Services\GoogleSheetService::class);
        if (!$gs->isReady()) return [];

        $values = $gs->getValues('BorrowDetails!A:J')->getValues();
        if (!$values || count($values) <= 1) return [];

        $data = array_slice($values, 1);
        $rows = [];

        foreach ($data as $i => $r) {
            $rows[] = [
                'RowNumber'    => $i + 2, // ✅ real sheet row number
                'CardID'       => $r[0] ?? '',
                'BorrowerName' => $r[1] ?? '',
                'UserID'       => $r[2] ?? '',
                'ItemID'       => $r[3] ?? '',
                'BorrowDate'   => $r[4] ?? '',
                'ReturnDate'   => $r[5] ?? '',
                'BorrowedAt'   => $r[6] ?? '',
                'ReturnedAt'   => $r[7] ?? '',
                'Status'       => $r[8] ?? '',
                'Remarks'      => $r[9] ?? '',
            ];
        }

        // ✅ show newest first but keep true row numbers
        $rows = array_reverse($rows);
        return array_slice($rows, 0, 12);
    } catch (\Throwable $e) {
        \Log::error('readRecentFromSheet failed: ' . $e->getMessage());
        return [];
    }
}



    protected function findLatestBorrowRowByUid(string $itemId): ?array
    {
        try {
            $gs = app(\App\Services\GoogleSheetService::class);
            $values = $gs->getValues('BorrowDetails!A:I')->getValues();
            if (!$values || count($values) <= 1) return null;

            $data = array_slice($values, 1);
            for ($i = count($data) - 1; $i >= 0; $i--) {
                $row = $data[$i];
                if (trim($row[3] ?? '') === $itemId) {
                    return [
                        'CardID'       => $row[0] ?? '',
                        'BorrowerName' => $row[1] ?? '',
                        'UserID'       => $row[2] ?? '',
                        'ItemID'       => $row[3] ?? '',
                        'BorrowDate'   => $row[4] ?? '',
                        'ReturnDate'   => $row[5] ?? '',
                        'BorrowedAt'   => $row[6] ?? '',
                        'ReturnedAt'   => $row[7] ?? '',
                        'Status'       => $row[8] ?? '',
                        'RowNumber'    => $i + 2,
                    ];
                }
            }
            return null;
        } catch (\Throwable $e) {
            \Log::error('findLatestBorrowRowByUid failed: ' . $e->getMessage());
            return null;
        }
    }
    

   public function delete(Request $request, $rowIndex)
{
    try {
        $gs = app(\App\Services\GoogleSheetService::class);
        if (!$gs->isReady()) {
            return back()->with('error', 'Google Sheets API not configured.');
        }

        $sheetName = 'BorrowDetails';
        $rowIndex = intval($rowIndex);

        $success = $gs->deleteRowByRowIndex($sheetName, $rowIndex);

        if ($success) {
            return back()->with('success', "✅ Successfully deleted row {$rowIndex}.");
        }

        return back()->with('error', '❌ Failed to delete the selected record.');
    } catch (\Throwable $e) {
        \Log::error('❌ Delete error: ' . $e->getMessage());
        return back()->with('error', 'Delete failed: ' . $e->getMessage());
    }
}

    // Return Method
    public function returnIndex()
    {
        return view('return.index');
    }


    public function fetchBorrowedItems($cardUid)
{
    try {
        $gs = app(\App\Services\GoogleSheetService::class);
        if (!$gs->isReady()) {
            return response()->json(['error' => 'Google Sheets API not configured'], 500);
        }

        // Read BorrowDetails sheet
        $values = $gs->getValues('BorrowDetails!A:I')->getValues();
        if (!$values || count($values) <= 1) {
            return response()->json(['error' => 'No borrow records found'], 404);
        }

        $rows = array_slice($values, 1); // skip header
        $latestBorrowed = [];
        $latestStatusByItem = [];

        // 🟢 Step 1: Find the most recent status for each item ID
        for ($i = count($rows) - 1; $i >= 0; $i--) {
            $r = $rows[$i];
            $itemId = trim($r[3] ?? '');
            $status = strtolower(trim($r[8] ?? ''));

            if ($itemId && !isset($latestStatusByItem[$itemId])) {
                $latestStatusByItem[$itemId] = $status;
            }
        }

        // 🟢 Step 2: Collect only active (still borrowed) items for this specific card
        for ($i = count($rows) - 1; $i >= 0; $i--) {
            $r = $rows[$i];
            $card   = trim($r[0] ?? '');
            $status = strtolower(trim($r[8] ?? ''));
            $itemId = trim($r[3] ?? '');

            if ($card !== $cardUid || empty($itemId)) continue;

            // Skip if latest known status is not "borrowed"
            if (($latestStatusByItem[$itemId] ?? '') !== 'borrowed') continue;

            // Only add if not already listed and current row is borrowed
            if (!isset($latestBorrowed[$itemId]) && $status === 'borrowed') {
                $item = \App\Models\Item::where('item_id', $itemId)->first();

                $latestBorrowed[$itemId] = [
                    'ItemID'       => $itemId,
                    'BorrowerName' => $r[1] ?? '',
                    'UserID'       => $r[2] ?? '',
                    'Name'         => $item->name ?? '',
                ];
            }
        }

        if (empty($latestBorrowed)) {
            return response()->json(['error' => 'No active borrowed items for this card'], 404);
        }

        return response()->json([
            'success' => true,
            'items'   => array_values($latestBorrowed)
        ]);
    } catch (\Throwable $e) {
        \Log::error('fetchBorrowedItems failed: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to fetch borrowed items.'], 500);
    }
}



public function confirmReturnByCard(Request $request)
{
    $cardUid = $request->input('card_uid');
    $itemIds = $request->input('item_ids', []);

    if (empty($itemIds)) {
        return back()->with('error', 'No items selected to return.');
    }

    try {
        $gs = app(\App\Services\GoogleSheetService::class);
        if (!$gs->isReady()) {
            return back()->with('error', 'Google Sheets API not configured.');
        }

        foreach ($itemIds as $itemId) {
            $last = $this->findLatestBorrowRowByUid($itemId);
            if (!$last) continue;

            $row = [
                $last['CardID'],
                $last['BorrowerName'],
                $last['UserID'],
                $last['ItemID'],
                $last['BorrowDate'],
                $last['ReturnDate'],
                $last['BorrowedAt'],
                now('Asia/Brunei')->format('g:i A'),
                'available',
            ];

            $gs->appendRow($row, 'BorrowDetails!A:I');
            $this->updateItemStatus($itemId, 'available');
            \App\Models\Item::where('item_id', $itemId)->update(['status' => 'available']);
        }

        return back()->with('success', '✅ All items returned successfully.');
    } catch (\Throwable $e) {
        \Log::error('confirmReturnByCard failed: ' . $e->getMessage());
        return back()->with('error', '❌ Failed: ' . $e->getMessage());
    }
}

}
