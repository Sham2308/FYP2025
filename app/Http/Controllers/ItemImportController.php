<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use App\Services\GoogleSheetService; // uncomment if you already built this

class ItemImportController extends Controller
{
    public function google(Request $request/*, GoogleSheetService $sheets*/)
    {
        // ⚡ for now, just a placeholder
        // later you can wire it to actually pull rows from Google Sheets

        // Example if using your GoogleSheetService:
        // $result = $sheets->importItems();

        return back()->with('status', 'Imported items from Google (placeholder).');
    }
}
