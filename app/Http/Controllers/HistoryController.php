<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\BorrowDetail;
use Carbon\Carbon;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        // Start query builder
        $query = BorrowDetail::query();

        // 🔍 Filter by UserID or BorrowerName
        if ($request->filled('user')) {
            $search = $request->input('user');
            $query->where(function ($q) use ($search) {
                $q->where('UserID', 'LIKE', "%{$search}%")
                  ->orWhere('BorrowerName', 'LIKE', "%{$search}%");
            });
        }

        // 🔍 Filter by Status
        if ($request->filled('status')) {
            $query->where('Status', $request->input('status'));
        }

        // 🔍 Filter by date range (BorrowDate)
        if ($request->filled('from')) {
            $query->whereDate('BorrowDate', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('BorrowDate', '<=', $request->input('to'));
        }

        // ✅ Fetch results (latest first)
        $history = $query->orderByDesc('id')->get();

        return view('history.index', [
            'history' => $history,
            'error'   => $history->isEmpty() ? 'No history data found.' : null,
        ]);
    }

    // 🔹 Import BorrowDetails sheet as CSV and save to MySQL
    public function importFromGoogleSheet()
    {
        $url    = config('services.google.webapp_url');
        $secret = config('services.google.secret');

        if (empty($url) || empty($secret)) {
            return back()->with('error', 'Google WebApp URL or secret is not configured.');
        }

        try {
            // ✅ Ask Apps Script for history
            $response = Http::timeout(20)->asJson()->post($url, [
                'secret' => $secret,
                'type'   => 'history',
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to reach Google Sheets: ' . $e->getMessage());
        }

        if ($response->failed()) {
            return back()->with('error', 'Failed to fetch history (HTTP ' . $response->status() . ').');
        }

        $data = $response->json();

        if (!$data['ok'] || empty($data['rows'])) {
            return back()->with('error', 'No history rows found.');
        }

        // ✅ Clear old records
        BorrowDetail::truncate();

        $inserted = 0;
        foreach ($data['rows'] as $row) {
            BorrowDetail::create([
                'Timestamp'     => $row['Timestamp']    ?? null,
                'BorrowID'      => $row['BorrowID']     ?? null,
                'UserID'        => $row['UserID']       ?? null,
                'BorrowerName'  => $row['BorrowerName'] ?? null,
                'UID'           => $row['UID']          ?? null,
                'AssetID'       => $row['AssetID']      ?? null,
                'Name'          => $row['Name']         ?? null,
                'BorrowDate'    => $row['BorrowDate']   ?? null,
                'ReturnDate'    => $row['ReturnDate']   ?? null,
                'BorrowedAt'    => $row['BorrowedAt']   ?? null,
                'ReturnedAt'    => $row['ReturnedAt']   ?? null,
                'Status'        => $row['Status']       ?? null,
                'Remarks'       => $row['Remarks']      ?? null,
            ]);
            $inserted++;
        }

        return redirect()->route('history.index')
            ->with('success', "BorrowDetails import successful. Replaced table with {$inserted} records.");
    }
}
