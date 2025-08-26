<!DOCTYPE html>
<html>
<head>
    <title>NFC Inventory Dashboard</title>
    <style>
        body { margin:0; font-family: system-ui, Arial, sans-serif; background:#fff; color:#111; }
        h2 { text-align:center; margin:24px 0 4px; }
        .links { text-align:center; margin-bottom:16px; }
        .links a { color:#2563eb; text-decoration:none; font-weight:600; margin:0 6px; }

        .cards { display:flex; gap:16px; justify-content:center; flex-wrap:wrap; margin: 10px auto 20px; }
        .card { min-width:220px; padding:16px; border-radius:12px; border:1px solid #e5e7eb; background:#f9fafb; text-align:center; }
        .card h3 { margin:0 0 6px; font-size:14px; color:#374151; }
        .card .num { font-size:28px; font-weight:800; }

        table { border-collapse: collapse; width: 95%; margin: 12px auto 40px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #f5f5f5; }
        .badge { padding:4px 10px; border-radius:999px; font-weight:600; display:inline-block; }
        .badge-good { background:#dcfce7; color:#166534; border:1px solid #86efac; }
        .badge-bad  { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
    </style>
</head>
<body>

    <h2>NFC Inventory Dashboard</h2>
    <div class="links">
        <a href="/">← Back to Home</a> |
        <a href="/nfc-scans">← Back to Scans</a>
    </div>

    <!-- Summary cards -->
    <div class="cards">
        <div class="card">
            <h3>Total Scans</h3>
            <div class="num">{{ $total }}</div>
        </div>
        <div class="card">
            <h3>Good Status</h3>
            <div class="num">{{ $goodCnt }}</div>
        </div>
        <div class="card">
            <h3>Bad Status</h3>
            <div class="num">{{ $badCnt }}</div>
        </div>
    </div>

    <!-- Per-item summary -->
    <h3 style="text-align:center; margin-top:10px;">Items Summary</h3>
    <table>
        <thead>
            <tr>
                <th>Item ID</th>
                <th>Item Name</th>
                <th>Total</th>
                <th>Good</th>
                <th>Bad</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $it)
                <tr>
                    <td>{{ $it->item_id ?? 'N/A' }}</td>
                    <td>{{ $it->item_name ?? 'N/A' }}</td>
                    <td>{{ $it->total_count }}</td>
                    <td><span class="badge badge-good">{{ $it->good_count }}</span></td>
                    <td><span class="badge badge-bad">{{ $it->bad_count }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5">No data</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- Latest scans -->
    <h3 style="text-align:center; margin-top:10px;">Latest Scans</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>UID</th>
                <th>Student ID</th>
                <th>User</th>
                <th>Item ID</th>
                <th>Item</th>
                <th>Status</th>
                <th>Scanned At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($latest as $scan)
                <tr>
                    <td>{{ $scan->id }}</td>
                    <td>{{ $scan->uid ?? 'N/A' }}</td>
                    <td>{{ $scan->student_id ?? 'N/A' }}</td>
                    <td>{{ $scan->user_name ?? 'N/A' }}</td>
                    <td>{{ $scan->item_id ?? 'N/A' }}</td>
                    <td>{{ $scan->item_name ?? 'N/A' }}</td>
                    <td>
                        @if($scan->status === 'good')
                            <span class="badge badge-good">good</span>
                        @elseif($scan->status === 'bad')
                            <span class="badge badge-bad">bad</span>
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $scan->created_at }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No scans yet</td></tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
