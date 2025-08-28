<!DOCTYPE html>
<html>
<head>
    <title>Borrow Item</title>
    <style>
        body { margin:0; font-family: system-ui, Arial, sans-serif; background:#ffffff; color:#111; }
        header { display:flex; justify-content:space-between; align-items:center;
                 padding:14px 30px; background:#2563eb; color:#fff; }
        header .logo { font-size:20px; font-weight:700; letter-spacing:0.5px; }
        header nav a { color:#fff; text-decoration:none; margin-left:20px; font-weight:600; }
        header nav a:hover { text-decoration:underline; }
        h2 { text-align:center; margin:24px 0 8px; }
        .wrap { width:95%; max-width:1100px; margin: 0 auto 40px; }
        .card { border:1px solid #e5e7eb; border-radius:12px; padding:16px; background:#f9fafb; margin-top:14px; }
        .row { display:flex; gap:12px; flex-wrap:wrap; }
        .row input, .row select, .row textarea {
            padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px;
            min-width:180px; flex:1;
        }
        textarea { min-height:80px; resize:vertical; }
        .actions { display:flex; gap:10px; margin-top:12px; }
        .btn { padding:10px 14px; border:none; border-radius:8px; font-weight:600; cursor:pointer; }
        .btn-success { background:#16a34a; color:#fff; }
        .btn-danger { background:#dc2626; color:#fff; }
        table { border-collapse: collapse; width: 100%; margin-top:16px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #f5f5f5; }
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

    <h2>Borrow Item</h2>

    <div class="wrap">

        <!-- Borrow Form -->
        <div class="card">
            <h3 style="margin:6px 0 12px;">Borrow Details</h3>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form action="{{ route('borrow.store') }}" method="POST">
                @csrf
                <div class="row">
                    <input name="user_id" type="text" placeholder="Student / Staff ID (e.g., 23FTTXXXX)" required>
                    <input name="borrower_name" type="text" placeholder="Borrower Name">
                </div>

                <div class="row" style="margin-top:10px;">
                    <input name="uid" type="text" placeholder="UID" required>
                    <input name="asset_id" type="text" placeholder="Asset ID">
                    <input name="type_id" type="text" placeholder="Type ID">
                </div>

                <div class="row" style="margin-top:10px;">
                    <input name="serial_no" type="text" placeholder="Serial No">
                    <input name="location_id" type="text" placeholder="Location ID">
                    <input name="qr_id" type="text" placeholder="QR ID">
                    <input name="purchase_date" type="date" placeholder="Purchase Date">
                </div>

                <div class="row" style="margin-top:10px;">
                    <input name="accessories" type="text" placeholder="Accessories">
                </div>

                <div class="row" style="margin-top:10px;">
                    <input name="detail" type="text" placeholder="Detail / Description">
                    <textarea name="remarks" placeholder="Remarks / Notes"></textarea>
                </div>

                <div class="row" style="margin-top:10px;">
                    <input name="borrow_date" type="date" placeholder="Borrow Date">
                    <input name="due_date" type="date" placeholder="Due Date / Return By">
                    <select name="condition_out">
                        <option value="">Condition Out…</option>
                        <option value="good">Good</option>
                        <option value="fair">Fair</option>
                        <option value="bad">Bad</option>
                    </select>
                </div>

                <div class="actions">
                    <button type="submit" class="btn btn-success">Save Borrow</button>
                    <button type="reset" class="btn btn-danger">Clear</button>
                </div>
            </form>
        </div>

        <!-- Borrow Log -->
        <div class="card">
            <h3 style="margin:6px 0 12px;">Recent Borrows</h3>
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>UID</th>
                        <th>Asset</th>
                        <th>Borrowed At</th>
                        <th>Returned At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($borrows as $borrow)
                    <tr>
                        <td>{{ $borrow->user_id }}</td>
                        <td>{{ $borrow->uid }}</td>
                        <td>{{ $borrow->item->asset_id ?? '-' }}</td>
                        <td>{{ $borrow->borrowed_at }}</td>
                        <td>{{ $borrow->returned_at ?? 'Not returned' }}</td>
                        <td>
                            @if(!$borrow->returned_at)
                                <form method="POST" action="{{ route('borrow.return', $borrow->uid) }}">
                                    @csrf
                                    <button class="btn btn-warning btn-sm">Return</button>
                                </form>
                            @else
                                <span class="text-muted">Returned</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">No borrow records yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>
