<?php

namespace App\Imports;

use App\Models\Item;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ItemsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Item([
            'uid'         => $row['uid'] ?? null,
            'asset_id'    => $row['asset_id'] ?? null,
            'name'        => $row['name'] ?? null,
            'detail'      => $row['detail'] ?? null,
            'accessories' => $row['accessories'] ?? null,
            'type_id'     => $row['type_id'] ?? null,
            'serial_no'   => $row['serial_no'] ?? null,
            'status'      => $row['status'] ?? 'available',
            'qr_id'       => $row['qr_id'] ?? null,
            'remarks'     => $row['remarks'] ?? null,
        ]);
    }
}
