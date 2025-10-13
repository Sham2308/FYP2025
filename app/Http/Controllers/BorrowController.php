<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BorrowController extends Controller
{
    public function index()
    {
        $items = \App\Models\Item::orderBy('asset_id')->get();
        $recent = $this->readRecentFromSheet(); // array of rows (newest first)

        return view('borrow.index', [
            'items'  => $items,
            'recent' => $recent,
        ]);
    }
    /**
     * 🔍 Fetch user info from Google Sheet by Card UID
     * Used by the scanner to auto-fill Student ID and Name
     */
    public function getUserByUid($cardUid)
    {
        try {
            $gs = app(\App\Services\GoogleSheetService::class);
            if (!$gs->isReady()) {
                return response()->json(['error' => 'Google Sheets API not configured'], 500);
            }

            // Read Users sheet (UID | StudentID | Name)
            $values = $gs->getValues('Users!A:C')->getValues();
            if (!$values || count($values) <= 1) {
                return response()->json(['error' => 'No user data in sheet'], 404);
            }

            foreach ($values as $i => $row) {
                if ($i === 0) continue; // Skip header row
                if (isset($row[0]) && trim($row[0]) === trim($cardUid)) {
                    return response()->json([
                        'uid'        => $cardUid,
                        'student_id' => $row[1] ?? '',
                        'name'       => $row[2] ?? '',
                    ]);
                }
            }

            return response()->json(['error' => 'User not found in Google Sheet'], 404);
        } catch (\Throwable $e) {
            \Log::error('getUserByUid failed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    /**
     * Fetch an item by UID from Google Sheets
     * Used for scanning NFC/QR sticker
     */
    public function fetchItem($uid)
    {
        try {
            $gs = app(\App\Services\GoogleSheetService::class);
            if (!$gs->isReady()) {
                return response()->json(['error' => 'Google Sheets API not configured'], 500);
            }

            // Fetch all item data
            $values = $gs->getValues('Items!A:E')->getValues();
            if (!$values || count($values) <= 1) {
                return response()->json(['error' => 'No items in Google Sheet'], 404);
            }

            // Find item by UID (Column A)
            foreach ($values as $i => $row) {
                if ($i === 0) continue; // skip header
                if (isset($row[0]) && trim($row[0]) === trim($uid)) {
                    return response()->json([
                        'uid'       => $row[0] ?? '',
                        'asset_id'  => $row[1] ?? '',
                        'name'      => $row[2] ?? '',
                        'status'    => $row[3] ?? '',
                        'purchased' => $row[4] ?? '',
                    ]);
                }
            }

            return response()->json(['error' => 'Item not found in Google Sheet'], 404);
        } catch (\Throwable $e) {
            \Log::error('fetchItem failed: '.$e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

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
            'items.*.uid'   => 'required|string',
        ]);

        try {
            $gs = app(\App\Services\GoogleSheetService::class);
            if (!$gs->isReady()) {
                return back()->with('error', 'Google Sheets API not configured.');
            }

            $latestRow = null;

            foreach ($request->items as $item) {
                $row = [
                    now()->format('Y-m-d H:i:s'),
                    '',
                    $request->user_id,
                    $request->borrower_name,
                    $item['uid'],
                    $item['asset_id'] ?? '',
                    $item['name'] ?? '',
                    $request->borrow_date ?? now()->toDateString(),
                    $request->due_date ?? '',
                    now()->toDateString(),
                    '',
                    'borrowed',
                ];

                // ✅ Add to Google Sheet
                $gs->appendRow($row, 'BorrowDetails!A:L');
                $latestRow = $row;

                // ✅ Update in Google Sheet
                $this->updateItemStatus($item['uid'], 'borrowed');

                // ✅ Update local DB table
                \App\Models\Item::where('uid', $item['uid'])->update(['status' => 'borrowed']);
            }

            session(['recentBorrow' => [$latestRow]]);
            return back()->with('success', '✅ Saved to Google Sheets and updated item status.');
        } catch (\Throwable $e) {
            Log::error('publicStore failed: '.$e->getMessage());
            return back()->with('error', '❌ Failed: '.$e->getMessage());
        }
    }

    public function returnByUid(Request $request, string $uid)
    {
        try {
            $gs = app(\App\Services\GoogleSheetService::class);
            if (!$gs->isReady()) return back()->with('error', 'Google Sheets API not configured.');

            $last = $this->findLatestBorrowRowByUid($uid);
            if (!$last) return back()->with('error', 'No borrow row found for this UID.');

            $row = [
                now()->format('Y-m-d H:i:s'),
                '',
                $last['UserID'] ?? '',
                $last['BorrowerName'] ?? '',
                $uid,
                $last['AssetID'] ?? '',
                $last['Name'] ?? '',
                $last['BorrowDate'] ?? '',
                $last['ReturnDate'] ?? '',
                $last['BorrowedAt'] ?? '',
                now()->toDateString(),
                'available',
            ];

            // ✅ Append return record
            $gs->appendRow($row, 'BorrowDetails!A:L');

            // ✅ Update status both sides
            $this->updateItemStatus($uid, 'available');
            \App\Models\Item::where('uid', $uid)->update(['status' => 'available']);

            session(['recentBorrow' => [$row]]);
            return back()->with('success', '✅ Marked returned and status updated.');
        } catch (\Throwable $e) {
            Log::error('returnByUid failed: '.$e->getMessage());
            return back()->with('error', '❌ Failed to mark returned.');
        }
    }

    protected function updateItemStatus(string $uid, string $status): void
    {
        try {
            $gs = app(\App\Services\GoogleSheetService::class);
            if (!$gs->isReady()) return;

            $values = $gs->getValues('Items!A:E')->getValues();
            if (!$values || count($values) <= 1) return;

            foreach ($values as $i => $row) {
                if ($i === 0) continue;
                if (isset($row[0]) && trim($row[0]) === trim($uid)) {
                    $rowNum = $i + 1;
                    $range = "D{$rowNum}";
                    $gs->updateRow('Items', $range, [[$status]]);
                    Log::info("Updated UID {$uid} status to {$status}");
                    break;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('updateItemStatus failed: '.$e->getMessage());
        }
    }

    public function deleteBorrow($uid)
    {
        try {
            $service = app(\App\Services\GoogleSheetService::class);
            if (!$service->isReady()) {
                return response()->json(['message' => 'Google Sheets API not configured.'], 500);
            }

            $rows = $service->getValues('BorrowDetails!A:L')->getValues();
            $targetRow = null;
            foreach ($rows as $i => $r) {
                if ($i === 0) continue;
                if (isset($r[4]) && trim($r[4]) === trim($uid)) {
                    $targetRow = $i + 1;
                    break;
                }
            }

            if ($targetRow) {
                $service->deleteRow('BorrowDetails', $targetRow);
                // ✅ also set local item back to available
                \App\Models\Item::where('uid', $uid)->update(['status' => 'available']);
                return response()->json(['message' => '✅ Borrow record deleted and status reset.']);
            }

            return response()->json(['message' => 'UID not found in Google Sheet.'], 404);
        } catch (\Throwable $e) {
            Log::error('Delete Borrow Error: '.$e->getMessage());
            return response()->json(['message' => '❌ Failed to delete: '.$e->getMessage()], 500);
        }
    }

    protected function readRecentFromSheet(): array
    {
        try {
            $gs = app(\App\Services\GoogleSheetService::class);
            if (!$gs->isReady()) return [];

            $values = $gs->getValues('BorrowDetails!A:L')->getValues();
            if (!$values || count($values) <= 1) return [];

            $data = array_slice($values, 1);
            $last = end($data);

            if (!$last) return [];

            return [[
                'Timestamp'    => $last[0] ?? '',
                'BorrowID'     => $last[1] ?? '',
                'UserID'       => $last[2] ?? '',
                'BorrowerName' => $last[3] ?? '',
                'UID'          => $last[4] ?? '',
                'AssetID'      => $last[5] ?? '',
                'Name'         => $last[6] ?? '',
                'BorrowDate'   => $last[7] ?? '',
                'ReturnDate'   => $last[8] ?? '',
                'BorrowedAt'   => $last[9] ?? '',
                'ReturnedAt'   => $last[10] ?? '',
                'Status'       => $last[11] ?? '',
            ]];
        } catch (\Throwable $e) {
            \Log::warning('readRecentFromSheet error: ' . $e->getMessage());
            return [];
        }
    }

    protected function findLatestBorrowRowByUid(string $uid): ?array
    {
        $rows = $this->readRecentFromSheetFull();
        $filtered = array_values(array_filter($rows, fn($r)=>($r['UID'] ?? '') === $uid));
        if (!$filtered) return null;
        usort($filtered, fn($a,$b)=>strcmp($b['Timestamp'],$a['Timestamp']));
        return $filtered[0];
    }

    protected function readRecentFromSheetFull(): array
    {
        try {
            $gs = app(\App\Services\GoogleSheetService::class);
            if (!$gs->isReady()) return [];
            $values = $gs->getValues('BorrowDetails!A:L')->getValues();
            if (!$values || count($values) <= 1) return [];
            $rows = [];
            foreach (array_slice($values, 1) as $r) {
                $rows[] = [
                    'Timestamp'    => $r[0]  ?? '',
                    'BorrowID'     => $r[1]  ?? '',
                    'UserID'       => $r[2]  ?? '',
                    'BorrowerName' => $r[3]  ?? '',
                    'UID'          => $r[4]  ?? '',
                    'AssetID'      => $r[5]  ?? '',
                    'Name'         => $r[6]  ?? '',
                    'BorrowDate'   => $r[7]  ?? '',
                    'ReturnDate'   => $r[8]  ?? '',
                    'BorrowedAt'   => $r[9]  ?? '',
                    'ReturnedAt'   => $r[10] ?? '',
                    'Status'       => $r[11] ?? '',
                ];
            }
            return $rows;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
