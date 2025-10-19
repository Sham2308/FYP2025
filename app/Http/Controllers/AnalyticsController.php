<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;


class AnalyticsController extends Controller
{
    public function index()
    {
         // Existing counts
        $totalItems = Item::count();
        $available = Item::where('status', 'available')->count();
        $borrowed = Item::where('status', 'borrowed')->count();
        $underRepair = Item::where('status', 'under repair')->count();

        // ✅ Fetch latest reports (same data as notifications)
        $latestReports = Report::latest()->take(5)->get();

        // ✅ (Optional) also show unread notifications for reference
        $notifications = Auth::user()->notifications()->latest()->take(10)->get();

        return view('analytics.index', compact(
            'totalItems', 'available', 'borrowed', 'underRepair',
            'latestReports', 'notifications'
        ));
    }
}
