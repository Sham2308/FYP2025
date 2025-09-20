<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class TechnicalDashboardController extends Controller
{
    public function index()
    {
        $csvUrl = env('BORROW_SHEET_CSV');
        $headers = [];
        $rows = [];

        if ($csvUrl) {
            $resp = Http::timeout(10)->get($csvUrl);
            if ($resp->ok()) {
                [$headers, $rows] = $this->parseCsv($resp->body());
            }
        }

        return view('technical.dashboard', compact('headers', 'rows'));
    }

    /**
     * @return array{0: array<int,string>, 1: array<int,array<int,string>>}
     */
    private function parseCsv(string $csv): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($csv));
        $headers = [];
        $rows = [];

        foreach ($lines as $i => $line) {
            $fields = str_getcsv($line);
            if ($i === 0) {
                $headers = array_map(fn($h) => trim((string)$h), $fields);
                continue;
            }
            $rows[] = array_map(fn($v) => trim((string)$v), $fields);
        }
        return [$headers, $rows];
    }
}
