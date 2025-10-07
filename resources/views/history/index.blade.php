<!DOCTYPE html>
<html>
<head>
    <title>TapNBorrow</title>
    <link rel="icon" type="image/png" href="{{ asset('images/main-logo.png') }}">
    <style>
        body { margin:0; font-family: system-ui, Arial, sans-serif; background:#ffffff; color:#111; }
        header {
            display:flex; justify-content:space-between; align-items:center;
            padding:14px 30px; background:#2563eb; color:#fff;
        }
        /* brand (logo + wordmark) */
        .brand { display:flex; align-items:center; gap:10px; text-decoration:none; color:#fff; }
        .brand img { height:26px; width:auto; display:block; }
        header .logo { font-size:20px; font-weight:700; letter-spacing:0.5px; }

        header nav a { color:#fff; text-decoration:none; margin-left:20px; font-weight:600; }
        header nav a:hover { text-decoration:underline; }
        h2 { text-align:center; margin:24px 0 8px; }
        .wrap { width:95%; max-width:1300px; margin: 0 auto 40px; }
        .card { border:1px solid #e5e7eb; border-radius:12px; padding:16px; background:#f9fafb; margin-top:14px; }
        .alert { padding:12px; border-radius:8px; margin-bottom:16px; }
        .alert-danger { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }
        table { border-collapse: collapse; width: 100%; margin-top:16px; font-size:14px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; vertical-align: middle; }
        th { background: #f5f5f5; }
        .btn-import { background:#16a34a; color:#fff; padding:8px 14px; border:none; border-radius:6px; cursor:pointer; font-weight:600; margin-bottom:12px; }
        .btn-import:hover { background:#15803d; }
    </style>
</head>
<body>

    @php
    use Carbon\Carbon;

    /**
     * Safely format a date-like value.
     * - Handles empty values
     * - Handles UNIX epoch seconds
     * - Handles Google Sheets/Excel serial dates
     * - Parses common date strings
     * - Returns '-' for non-dates (e.g., UIDs like 2284DC4A)
     */
    $safeDate = function ($v, $fmt = 'Y-m-d H:i') {
        try {
            if ($v === null || $v === '') return '-';

            // If already a DateTime/Carbon
            if ($v instanceof \DateTimeInterface) {
                return Carbon::instance($v)
                    ->tz(config('app.timezone', 'Asia/Brunei'))
                    ->format($fmt);
            }

            // Numeric cases
            if (is_numeric($v)) {
                $num = (float) $v;

                // UNIX epoch seconds (rough window 2001–2100)
                if ($num > 1000000000 && $num < 4102444800) {
                    return Carbon::createFromTimestampUTC((int)$num)
                        ->tz(config('app.timezone', 'Asia/Brunei'))
                        ->format($fmt);
                }

                // Google Sheets / Excel serial date (days since 1899-12-30)
                if ($num > 25569 && $num < 600000) {
                    $seconds = (int) round(($num - 25569) * 86400);
                    return Carbon::createFromTimestampUTC($seconds)
                        ->tz(config('app.timezone', 'Asia/Brunei'))
                        ->format($fmt);
                }
            }

            // String parse for common formats (safe)
            $s = (string)$v;
            if (preg_match('/\d/', $s)) {
                $c = Carbon::make($s); // returns null if not parseable
                if ($c) {
                    return $c->tz(config('app.timezone', 'Asia/Brunei'))->format($fmt);
                }
            }

            return '-';
        } catch (\Throwable $e) {
            return '-';
        }
    };
    @endphp

    <!-- Header -->
    <header>
        <a href="/" class="brand">
            <img src="{{ asset('images/icon-logo.png') }}" alt="TapNBorrow logo">
            <div class="logo">TapNBorrow</div>
        </a>
        <nav>
            <a href="/">Home</a>
            <a href="/borrow">Borrow</a>
            <a href="{{ route('nfc.inventory') }}">Inventory</a>
            <a href="{{ route('history.index') }}">History</a>
        </nav>
    </header>

    <h2>Borrow History</h2>

    <div class="wrap">
        <div class="card">

            <!-- ✅ Import Button -->
            <form action="{{ route('history.import.google') }}" method="POST">
                @csrf
                <button type="submit" class="btn-import">Import from Google Sheets</button>
            </form>

            <!-- ✅ Show error -->
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if(!empty($error))
                <div class="alert alert-danger">{{ $error }}</div>
            @endif

            <!-- ✅ Show history table -->
            @if(!empty($history))
                <table>
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>BorrowID</th>
                            <th>UserID</th>
                            <th>Borrower Name</th>
                            <th>UID</th>
                            <th>AssetID</th>
                            <th>Name</th>
                            <th>Borrow Date</th>
                            <th>Return Date</th>
                            <th>Borrowed At</th>
                            <th>Returned At</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history as $row)
                        <tr>
                            <td>{{ $safeDate($row['Timestamp']  ?? null, 'Y-m-d H:i') }}</td>
                            <td>{{ $row['BorrowID'] ?? '-' }}</td>
                            <td>{{ $row['UserID'] ?? '-' }}</td>
                            <td>{{ $row['BorrowerName'] ?? '-' }}</td>
                            <td>{{ $row['UID'] ?? '-' }}</td>
                            <td>{{ $row['AssetID'] ?? '-' }}</td>
                            <td>{{ $row['Name'] ?? '-' }}</td>
                            <td>{{ $safeDate($row['BorrowDate'] ?? null, 'Y-m-d') }}</td>
                            <td>{{ $safeDate($row['ReturnDate'] ?? null, 'Y-m-d') }}</td>
                            <td>{{ $safeDate($row['BorrowedAt'] ?? null, 'Y-m-d H:i') }}</td>
                            <td>{{ $safeDate($row['ReturnedAt'] ?? null, 'Y-m-d H:i') }}</td>
                            <td>{{ $row['Status'] ?? '-' }}</td>
                            <td>{{ $row['Remarks'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

        </div>
    </div>

</body>
</html>
