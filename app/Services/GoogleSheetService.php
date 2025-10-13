<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Log;

class GoogleSheetService
{
    private ?Sheets $service = null;
    private ?string $spreadsheetId = null;

    public function __construct()
    {
        // ✅ Allow disabling via .env
        $enabled = filter_var(env('USE_GOOGLE_SHEETS_API', true), FILTER_VALIDATE_BOOLEAN);
        if (!$enabled) {
            Log::warning('GoogleSheetService disabled: USE_GOOGLE_SHEETS_API=false');
            return;
        }

        // ✅ Spreadsheet ID (from .env or config/services.php)
        $this->spreadsheetId = config('services.google.sheet_id', env('GOOGLE_SHEET_ID'));
        if (empty($this->spreadsheetId)) {
            Log::error('❌ GoogleSheetService: Missing GOOGLE_SHEET_ID');
            return;
        }

        // ✅ Resolve credentials path
        $pathFromEnv = env('GOOGLE_SHEETS_CREDENTIALS_PATH', 'storage/app/google/credentials.json');
        $fullPath = $this->resolvePath($pathFromEnv);

        if (!is_file($fullPath)) {
            Log::error("❌ GoogleSheetService: credentials file not found at {$fullPath}");
            return;
        }

        try {
            $client = new Client();
            $client->setApplicationName('TapNBorrow Google Sheets API');
            $client->setAuthConfig($fullPath);
            $client->setScopes([Sheets::SPREADSHEETS]);
            $client->setAccessType('offline');

            $this->service = new Sheets($client);
            Log::info("✅ GoogleSheetService initialized successfully. Using: {$fullPath}");
        } catch (\Throwable $e) {
            Log::error('❌ GoogleSheetService init failed: ' . $e->getMessage());
            $this->service = null;
        }
    }

    // ------------------------------------------------------
    // ✅ Utility functions
    // ------------------------------------------------------

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }
        if (str_starts_with($path, 'storage/')) {
            return base_path($path);
        }
        if (is_file(base_path($path))) {
            return base_path($path);
        }
        return storage_path(str_replace('storage/', '', $path));
    }

    public function isReady(): bool
    {
        return $this->service !== null && !empty($this->spreadsheetId);
    }

    private function assertReady(): void
    {
        if (!$this->isReady()) {
            throw new \RuntimeException('Google Sheets API not configured — check .env or credentials path.');
        }
    }

    // ------------------------------------------------------
    // ✅ Main API wrappers
    // ------------------------------------------------------

    public function getValues(string $range = 'Sheet1!A:Z')
    {
        $this->assertReady();
        return $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
    }

    public function appendRow(array $values, string $range = 'BorrowDetails!A:Z')
    {
        $this->assertReady();

        $body = new \Google\Service\Sheets\ValueRange(['values' => [$values]]);
        $params = ['valueInputOption' => 'USER_ENTERED'];

        return $this->service->spreadsheets_values->append(
            $this->spreadsheetId,
            $range,
            $body,
            $params
        );
    }

    // ✅ Delete row (for BorrowDetails or Items)
    public function deleteRow(string $sheetName, int $rowNumber): bool
    {
        $this->assertReady();

        try {
            $requests = [
                new \Google\Service\Sheets\Request([
                    'deleteDimension' => [
                        'range' => [
                            'sheetId'   => 0, // default to first sheet tab (adjust if needed)
                            'dimension' => 'ROWS',
                            'startIndex'=> $rowNumber - 1, // 0-based index
                            'endIndex'  => $rowNumber
                        ]
                    ]
                ])
            ];

            $batchUpdateRequest = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                'requests' => $requests
            ]);

            $this->service->spreadsheets->batchUpdate($this->spreadsheetId, $batchUpdateRequest);
            Log::info("✅ Deleted row {$rowNumber} from {$sheetName}");
            return true;
        } catch (\Throwable $e) {
            Log::error('❌ deleteRow() failed: ' . $e->getMessage());
            return false;
        }
    }

    // ✅ Update a specific cell range (for return/update status)
    public function updateRow(string $sheetName, string $range, array $values): bool
    {
        $this->assertReady();

        try {
            $body = new \Google\Service\Sheets\ValueRange(['values' => [$values]]);
            $params = ['valueInputOption' => 'USER_ENTERED'];

            $this->service->spreadsheets_values->update(
                $this->spreadsheetId,
                "{$sheetName}!{$range}",
                $body,
                $params
            );
            Log::info("✅ Updated {$sheetName}!{$range}");
            return true;
        } catch (\Throwable $e) {
            Log::error('❌ updateRow() failed: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteRowByAssetId(string $assetId, string $range = 'Items!A:Z'): bool
    {
        $this->assertReady();

        $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
        $values = $response->getValues() ?? [];
        if (empty($values)) return false;

        foreach ($values as $index => $row) {
            if (isset($row[1]) && trim((string)$row[1]) === trim((string)$assetId)) {
                $requests = [
                    new \Google\Service\Sheets\Request([
                        'deleteDimension' => [
                            'range' => [
                                'sheetId'   => 0,
                                'dimension' => 'ROWS',
                                'startIndex'=> $index,
                                'endIndex'  => $index + 1,
                            ]
                        ]
                    ])
                ];
                $batchUpdateRequest = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                    'requests' => $requests
                ]);
                $this->service->spreadsheets->batchUpdate($this->spreadsheetId, $batchUpdateRequest);
                return true;
            }
        }
        return false;
    }
}
