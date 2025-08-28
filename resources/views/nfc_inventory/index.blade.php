<!DOCTYPE html>
<html>
<head>
    <title>NFC Inventory Dashboard</title>
    <style>
        body { margin:0; font-family: system-ui, Arial, sans-serif; background:#fff; color:#111; }
        header { display:flex; justify-content:space-between; align-items:center;
                 padding:14px 30px; background:#2563eb; color:#fff; }
        header .logo { font-size:20px; font-weight:700; letter-spacing:0.5px; }
        header nav a { color:#fff; text-decoration:none; margin-left:20px; font-weight:600; }
        header nav a:hover { text-decoration:underline; }
        h2 { text-align:center; margin:24px 0 6px; }
        .wrap { width:95%; margin: 0 auto 40px; }
        .card { border:1px solid #e5e7eb; border-radius:8px; padding:12px 16px; margin-top:12px; }
        .card h3 { margin:8px 0 12px; }
        .alert { padding:10px 12px; border-radius:6px; background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; margin: 10px auto; width:95%; }
        table { border-collapse: collapse; width: 100%; margin-top:14px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #f5f5f5; }
        .badge { padding:4px 10px; border-radius:999px; font-weight:600; display:inline-block; }
        .badge-good { background:#dcfce7; color:#166534; border:1px solid #86efac; }
        .badge-bad  { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
        .badge-na   { background:#e5e7eb; color:#374151; border:1px solid #d1d5db; }
        .section-title { margin: 18px 0 10px; }
        .import-btn { padding:8px 14px; border:1px solid #22c55e; background:#22c55e; color:#fff; border-radius:6px; cursor:pointer; font-weight:600; text-decoration:none; }
        .import-btn:hover { opacity:.92; }
    </style>
</head>
<body>

    <!-- Header -->
    <header>
        <div class="logo">TapNBorrow</div>
        <nav>
            <a href="/">Home</a>
            <a href="/nfc-scans">Scan</a>
            <a href="/borrow">Borrow</a>
            <a href="{{ route('nfc.inventory') }}">Inventory</a>
        </nav>
    </header>

    <h2>NFC Inventory Dashboard</h2>

    @if(session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert" style="background:#fee2e2;color:#991b1b;border:1px solid #fecaca;">
            {{ session('error') }}
        </div>
    @endif

    <div class="wrap">

        <!-- Import from Google Sheets -->
        <div class="card">
            <h3>Import Items (Google Sheets)</h3>
            <a href="{{ route('items.import.google') }}" class="import-btn">Import from Google Sheets</a>
            <p style="margin-top:8px; font-size:14px; color:#374151;">
                Data will sync from your connected Google Sheet into the Items table.
            </p>
        </div>

        <!-- Latest Scans Table -->
        <h3 class="section-title">Latest Scans</h3>
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

        <!-- Latest Items Table -->
        @isset($items)
            <h3 class="section-title" style="margin-top:20px;">Latest Items (Google Sheets)</h3>
            <table>
                <thead>
                <tr>
                    <th>UID</th>
                    <th>Asset_ID</th>
                    <th>Name</th>
                    <th>Detail</th>
                    <th>Accessories</th>
                    <th>Type_ID</th>
                    <th>Serial No</th>
                    <th>Status</th>
                    <th>QR_ID</th>
                    <th>Remarks</th>
                </tr>
                </thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->uid ?? '—' }}</td>
                        <td>{{ $item->asset_id ?? '—' }}</td>
                        <td>{{ $item->name ?? '—' }}</td>
                        <td>{{ $item->detail ?? '—' }}</td>
                        <td>{{ $item->accessories ?? '—' }}</td>
                        <td>{{ $item->type_id ?? '—' }}</td>
                        <td>{{ $item->serial_no ?? '—' }}</td>
                        <td>{{ $item->status ?? '—' }}</td>
                        <td>{{ $item->qr_id ?? '—' }}</td>
                        <td>{{ $item->remarks ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10">No items yet</td></tr>
                @endforelse
                </tbody>
            </table>
        @endisset

    </div>
</body>
</html>
