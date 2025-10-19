<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RecordsController extends Controller
{
    /**
     * Display the list of recent borrow records (from Google Sheets)
     */
    public function recentBorrows()
    {
        try {
            $gs = app(\App\Services\GoogleSheetService::class);

            if (!$gs || !$gs->isReady()) {
                return back()->with('error', 'Google Sheets not configured.');
            }

            // ✅ Fetch data from BorrowDetails sheet
            $values = $gs->getValues('BorrowDetails!A:J')->getValues();
            if (!$values || count($values) <= 1) {
                return view('records.recent', ['recent' => []]);
            }

            $data = array_slice($values, 1);
            $rows = [];

            foreach ($data as $i => $r) {
                $rows[] = [
                    'RowNumber'    => $i + 2,
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

            $rows = array_reverse($rows); // newest first
            return view('records.recent', ['recent' => $rows]);
        } catch (\Throwable $e) {
            \Log::error('RecordsController@recentBorrows failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load records.');
        }
    }
}
