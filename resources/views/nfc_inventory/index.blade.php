<!DOCTYPE html>
<html>
<head>
    <title>NFC Inventory Dashboard</title>
    <style>
        body { margin:0; font-family: system-ui, Arial, sans-serif; background:#fff; color:#111; }
        h2 { text-align:center; margin:24px 0 6px; }
        .links { text-align:center; margin-bottom:16px; }
        .links a { color:#2563eb; text-decoration:none; font-weight:600; margin:0 6px; }

        table { border-collapse: collapse; width: 95%; margin: 12px auto 40px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #f5f5f5; }
        .badge { padding:4px 10px; border-radius:999px; font-weight:600; display:inline-block; }
        .badge-good { background:#dcfce7; color:#166534; border:1px solid #86efac; }
        .badge-bad  { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
        .badge-na   { background:#e5e7eb; color:#374151; border:1px solid #d1d5db; }
    </style>
</head>
<body>

    <h2>NFC Inventory Dashboard</h2>
    <div class="links">
        <a href="/">← Back to Home</a> |
        <a href="/nfc-scans">← Back to Scans</a>
    </div>

    <!-- Latest scans (same columns as NFC scans page, no Action column) -->
    <h3 style="text-align:center; margin-top:10px;">Latest Scans</h3>
    <table>
        <thead>
            <tr>
                <th>UID</th>
                <th>Asset ID</th>
                <th>Name</th>
                <th>Detail</th>
                <th>Accessories</th>
                <th>Type ID</th>
                <th>Serial No</th>
                <th>Location ID</th>
                <th>Status</th>
                <th>Purchase Date</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($latest as $scan)
                <tr>
                    <td>{{ $scan->uid ?? '—' }}</td>
                    <td>{{ $scan->asset_id ?? '—' }}</td>
                    <td>{{ $scan->name ?? '—' }}</td>
                    <td>{{ $scan->detail ?? '—' }}</td>
                    <td>{{ $scan->accessories ?? '—' }}</td>
                    <td>{{ $scan->type_id ?? '—' }}</td>
                    <td>{{ $scan->serial_no ?? '—' }}</td>
                    <td>{{ $scan->location_id ?? '—' }}</td>
                    <td>
                        @php $st = $scan->status; @endphp
                        @if($st === 'good')
                            <span class="badge badge-good">good</span>
                        @elseif($st === 'bad')
                            <span class="badge badge-bad">bad</span>
                        @else
                            <span class="badge badge-na">{{ $st ?? 'N/A' }}</span>
                        @endif
                    </td>
                    <td>
                        @if(!empty($scan->purchase_date))
                            {{ \Illuminate\Support\Carbon::parse($scan->purchase_date)->format('Y-m-d') }}
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $scan->remarks ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="11">No scans yet</td></tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
