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

    /**
 * ✅ Allow controllers to safely access Google Sheets service
 */
public function getService(): ?\Google\Service\Sheets
{
    return $this->service;
}

public function getSpreadsheetId(): ?string
{
    return $this->spreadsheetId;
}



public function deleteRowByItemId(string $sheetName, string $itemId): bool
{
    if (!$this->service || !$this->spreadsheetId) {
        \Log::error('❌ Google Sheets not initialized.');
        return false;
    }

    try {
        $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $sheetName);
        $rows = $response->getValues();
        if (!$rows) return false;

        $rowIndex = null;
        foreach ($rows as $i => $row) {
            // assuming ItemID is in column D (index 3)
            if (isset($row[3]) && trim($row[3]) === $itemId) {
                $rowIndex = $i + 1; // +1 because Sheets are 1-indexed
                break;
            }
        }

        if (!$rowIndex) {
            \Log::warning("⚠️ ItemID {$itemId} not found in {$sheetName}");
            return false;
        }

        // Delete that row
        $batchUpdateRequest = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => [[
                'deleteDimension' => [
                    'range' => [
                        'sheetId' => $this->getSheetIdByName($sheetName),
                        'dimension' => 'ROWS',
                        'startIndex' => $rowIndex - 1,
                        'endIndex' => $rowIndex,
                    ],
                ],
            ]],
        ]);

        $this->service->spreadsheets->batchUpdate($this->spreadsheetId, $batchUpdateRequest);
        \Log::info("✅ Deleted row {$rowIndex} ({$itemId}) from {$sheetName}");
        return true;
    } catch (\Throwable $e) {
        \Log::error("❌ Failed to delete row by itemId: " . $e->getMessage());
        return false;
    }
}
public function deleteRowByRowIndex(string $sheetName, int $rowIndex): bool
{
    try {
        $this->assertReady();

        if ($rowIndex <= 1) {
            \Log::warning("⚠️ Attempted to delete header or invalid row index: {$rowIndex}");
            return false;
        }

        $sheetId = $this->getSheetIdByName($sheetName);

        $batchUpdateRequest = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => [[
                'deleteDimension' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'dimension' => 'ROWS',
                        'startIndex' => $rowIndex - 1,
                        'endIndex' => $rowIndex,
                    ],
                ],
            ]],
        ]);

        $this->service->spreadsheets->batchUpdate($this->spreadsheetId, $batchUpdateRequest);
        \Log::info("✅ Deleted row #{$rowIndex} from {$sheetName}");
        return true;
    } catch (\Throwable $e) {
        \Log::error("❌ deleteRowByRowIndex failed: " . $e->getMessage());
        return false;
    }
}



private function getSheetIdByName(string $sheetName)
{
    $spreadsheet = $this->service->spreadsheets->get($this->spreadsheetId);
    foreach ($spreadsheet->getSheets() as $sheet) {
        $properties = $sheet->getProperties();
        if ($properties->getTitle() === $sheetName) {
            return $properties->getSheetId();
        }
    }
    throw new \Exception("Sheet {$sheetName} not found");
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
    // ✅ Sync Item IDs (Column A) from Google Sheets into MySQL
public function syncItemsFromSheet(): int
{
    $this->assertReady();
    $range = 'Items!A:E'; // A = Item ID, B = Asset ID, C = Name, D = Status, E = Purchase Date

    try {
        $values = $this->getValues($range)->getValues();
        if (!$values || count($values) <= 1) {
            Log::warning('⚠️ No data found in Items sheet.');
            return 0;
        }

        $rows = array_slice($values, 1); // skip header
        $count = 0;

        foreach ($rows as $r) {
            $itemId   = $r[0] ?? ''; // ✅ Column A (Item ID)
            $assetId  = $r[1] ?? ''; // ✅ Column B (Asset ID)
            $name     = $r[2] ?? '';
            $status   = $r[3] ?? 'available';
            $purchase = $r[4] ?? '';

            if (empty($itemId)) continue; // must have Item ID

            \App\Models\Item::updateOrCreate(
                ['item_id' => $itemId], // <-- use item_id as key
                [
                    'asset_id'       => $assetId,
                    'name'           => $name,
                    'status'         => $status,
                    'purchase_date'  => $purchase,
                ]
            );
            $count++;
        }

        Log::info("✅ Synced {$count} items from Google Sheets.");
        return $count;
    } catch (\Throwable $e) {
        Log::error('❌ syncItemsFromSheet failed: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Update item status in the Items sheet by matching asset_id or item_id.
 */
public function updateItemStatus($assetIdOrItemId, $newStatus)
{
    try {
        if (!$this->isReady()) return false;

        $range = 'Items!A:H';
        $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
        $values = $response->getValues();

        if (!$values || count($values) < 2) return false;

        // Normalize newStatus (Title Case for sheet)
        $newStatusTitle = ucwords(strtolower(trim($newStatus)));

        // Find the matching row (asset_id or item_id)
        foreach ($values as $index => $row) {
            if ($index === 0) continue; // skip header
            $itemId  = $row[0] ?? ''; // Item ID
            $assetId = $row[1] ?? ''; // Asset ID

            if ($assetIdOrItemId === $itemId || $assetIdOrItemId === $assetId) {
                $cell = 'H' . ($index + 1); // Status column (H)
                $body = new \Google\Service\Sheets\ValueRange([
                    'values' => [[ $newStatusTitle ]]
                ]);
                $params = ['valueInputOption' => 'RAW'];
                $this->service->spreadsheets_values->update(
                    $this->spreadsheetId,
                    $cell,
                    $body,
                    $params
                );
                return true;
            }
        }

        return false;
    } catch (\Throwable $e) {
        \Log::error('updateItemStatus() failed: '.$e->getMessage());
        return false;
    }
}


}
