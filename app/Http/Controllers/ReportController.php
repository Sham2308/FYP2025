<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    /**
     * Show the standalone report form (guests allowed).
     */
    public function create(Request $request)
    {
        return view('reports.create');
    }

    /**
     * Store a new report. Works for both logged-in users and guests.
     */
    public function store(Request $request)
    {
        // Base rules (switched to item_asset_id -> items.asset_id)
        $rules = [
            'subject'        => ['required','string','max:191'],
            'category'       => ['nullable','string','max:50'],
            'priority'       => ['required','in:low,medium,high'],
            'item_asset_id'  => ['nullable','string','exists:items,asset_id'], // ← CHANGED
            'message'        => ['required','string','max:5000'],
            'attachments'    => ['nullable','array'],
            'attachments.*'  => ['file','max:5120'], // 5MB each (add mimes if you want)
        ];

        // If the user is NOT logged in, require guest info
        if (!$request->user()) {
            $rules['guest_name']  = ['required','string','max:191'];
            $rules['guest_email'] = ['required','email','max:191'];
        }

        $data = $request->validate($rules);

        // Set user_id only if logged in (null for guests)
        $data['user_id'] = optional($request->user())->id;

        // Handle attachments
        $paths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $paths[] = $file->store('reports', 'public');
            }
        }
        if (!empty($paths)) {
            $data['attachments'] = $paths;
        }

        // Create the report
        $report = Report::create($data);

        // Notify admins + technicians (database bell)
        try {
            $by    = $report->user->name ?? $report->guest_name ?? 'Guest';
            $title = 'New Report: '.$report->subject;
            $body  = 'Priority: '.ucfirst($report->priority).' • From: '.$by;

            $recipients = User::whereIn('role', ['admin','technical'])->get();

            foreach ($recipients as $user) {
                // admins get the admin report page; techs get their own dashboard to avoid 403
                $url = $user->role === 'admin'
                    ? route('admin.reports.show', $report)
                    : route('technical.dashboard');

                $user->notify(new \App\Notifications\GenericDatabaseNotification($title, $body, $url));
            }
        } catch (\Throwable $e) {
            \Log::error('Report notify failed', ['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Report submitted. Thank you!');
    }

    /**
     * Admin: list reports with filters.
     */
    public function adminIndex(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403);

        $q = Report::with(['user','item'])->latest();

        if ($s = $request->get('status'))   $q->where('status', $s);
        if ($p = $request->get('priority')) $q->where('priority', $p);
        if ($search = $request->get('search')) {
            $q->where(function($w) use ($search){
                $w->where('subject','like',"%{$search}%")
                  ->orWhere('message','like',"%{$search}%");
            });
        }

        $reports = $q->paginate(15)->withQueryString();

        return view('admin.reports.index', compact('reports'));
    }

    /**
     * Admin: show a single report.
     */
    public function show(Request $request, Report $report)
    {
        abort_unless($request->user()->role === 'admin', 403);
        return view('admin.reports.show', compact('report'));
    }

    /**
     * Admin: update report status.
     */
    public function updateStatus(Request $request, Report $report)
    {
        abort_unless($request->user()->role === 'admin', 403);

        $data = $request->validate([
            'status' => ['required','in:open,in_progress,closed'],
        ]);

        $report->update($data);

        return back()->with('success', 'Status updated.');
    }

    /**
     * Admin: download a single attachment by its index.
     */
    public function downloadAttachment(Request $request, Report $report, int $index)
    {
        abort_unless($request->user()->role === 'admin', 403);

        $files = $report->attachments ?? [];
        abort_unless(isset($files[$index]), 404, 'Attachment not found');

        return Storage::disk('public')->download($files[$index]);
    }
}