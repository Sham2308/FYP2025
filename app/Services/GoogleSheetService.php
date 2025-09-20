<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;

class GoogleSheetService
{
    private ?Sheets $service = null;
    private ?string $spreadsheetId = null;

    public function __construct()
    {
        // Only initialize when explicitly enabled
        $enabled = filter_var(env('USE_GOOGLE_SHEETS_API', false), FILTER_VALIDATE_BOOLEAN);
        if (!$enabled) {
            return; // CSV-only mode → do nothing
        }

        // Resolve creds path
        $pathFromEnv = env('GOOGLE_SHEETS_CREDENTIALS_PATH'); // e.g. storage/app/google/credentials.json
        if (!$pathFromEnv) {
            return; // not configured
        }

        // If .env uses storage/... prefer storage_path()
        $fullPath = str_starts_with($pathFromEnv, 'storage/')
            ? storage_path(substr($pathFromEnv, strlen('storage/')))
            : base_path($pathFromEnv);

        if (!is_file($fullPath)) {
            return; // file missing → stay in no-op mode (don’t throw)
        }

        $client = new Client();
        $client->setAuthConfig($fullPath);
        $client->setScopes([Sheets::SPREADSHEETS]);

        $this->service = new Sheets($client);
        // use config() if you have it, else env fallback
        $this->spreadsheetId = config('services.google.sheet_id', env('GOOGLE_SHEET_ID'));
    }

    public function isReady(): bool
    {
        return $this->service !== null && !empty($this->spreadsheetId);
    }

    public function appendRow(array $values, string $range = 'Items!A:Z')
    {
        $this->assertReady();

        $body = new \Google\Service\Sheets\ValueRange(['values' => [$values]]);
        $params = ['valueInputOption' => 'RAW'];

        return $this->service->spreadsheets_values->append(
            $this->spreadsheetId,
            $range,
            $body,
            $params
        );
    }

    public function deleteRowByAssetId(string $assetId, string $range = 'Items!A:Z'): bool
    {
        $this->assertReady();

        $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
        $values = $response->getValues() ?? [];
        if (empty($values)) return false;

        foreach ($values as $index => $row) {
            // Column B (index 1) is asset_id
            if (isset($row[1]) && (string)$row[1] === (string)$assetId) {
                $requests = [
                    new \Google\Service\Sheets\Request([
                        'deleteDimension' => [
                            'range' => [
                                'sheetId'  => 0, // adjust if not first tab
                                'dimension'=> 'ROWS',
                                'startIndex' => $index,
                                'endIndex'   => $index + 1,
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

    public function getValues(string $range = 'Users!A:Z')
    {
        $this->assertReady();

        return $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
    }

    public function getService(): ?Sheets
    {
        return $this->service;
    }

    private function assertReady(): void
    {
        if (!$this->isReady()) {
            throw new \RuntimeException('Google Sheets API not configured (CSV-only mode or missing credentials).');
        }
    }
}
