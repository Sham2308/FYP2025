<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NfcController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'NFC data received',
            'data'    => $request->all(),
        ]);
    }
}
