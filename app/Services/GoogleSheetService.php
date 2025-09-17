<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;

class GoogleSheetService
{
    private $service;
    private $spreadsheetId;

    public function __construct()
    {
        $client = new Client();
        $client->setAuthConfig(storage_path('app/google/credentials.json'));
        $client->addScope(Sheets::SPREADSHEETS);

        $this->service = new Sheets($client);
        $this->spreadsheetId = config('services.google.sheet_id');
    }

    public function appendRow(array $values, $range = 'Items!A:Z')
    {
        $body = new \Google\Service\Sheets\ValueRange([
            'values' => [$values]
        ]);
        $params = ['valueInputOption' => 'RAW'];

        return $this->service->spreadsheets_values->append(
            $this->spreadsheetId,
            $range,
            $body,
            $params
        );
    }

    public function deleteRowByAssetId($assetId, $range = 'Items!A:Z')
    {
        // Get all rows from sheet
        $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
        $values = $response->getValues();

        if (empty($values)) {
            return false;
        }

        foreach ($values as $index => $row) {
            // Column B (index 1) is asset_id
            if (isset($row[1]) && $row[1] === $assetId) {
                $requests = [
                    new \Google\Service\Sheets\Request([
                        'deleteDimension' => [
                            'range' => [
                                'sheetId' => 0, // first sheet (usually 0)
                                'dimension' => 'ROWS',
                                'startIndex' => $index,
                                'endIndex' => $index + 1,
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
    public function getValues($range = 'Users!A:Z')
    {
        return $this->service->spreadsheets_values->get(
            $this->spreadsheetId,
            $range
        );
    }
    public function getService()
    {
        return $this->service;
    }
}
