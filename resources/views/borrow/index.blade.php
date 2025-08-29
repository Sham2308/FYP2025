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
        .wrap { width:95%; max-width:1300px; margin: 0 auto 40px; display:flex; gap:20px; align-items:flex-start; }
        .card { border:1px solid #e5e7eb; border-radius:12px; padding:16px; background:#f9fafb; margin-top:14px; flex:1; }
        .row { display:flex; gap:12px; flex-wrap:wrap; }
        .row input, .row textarea {
            padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px;
            min-width:160px; flex:1;
        }
        input[readonly] { background:#e5e7eb; color:#374151; }
        textarea { min-height:60px; resize:vertical; flex:1; }
        .actions { display:flex; gap:10px; margin-top:12px; }
        .btn { padding:10px 14px; border:none; border-radius:8px; font-weight:600; cursor:pointer; }
        .btn-success { background:#16a34a; color:#fff; }
        .btn-danger { background:#dc2626; color:#fff; }
        .btn-warning { background:#f59e0b; color:#fff; }
        .btn-success:hover { background:#15803d; }
        .btn-danger:hover { background:#b91c1c; }
        .btn-warning:hover { background:#d97706; }
        .btn-sm { padding:6px 12px; font-size:13px; }
        table { border-collapse: collapse; width: 100%; margin-top:16px; font-size:14px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; vertical-align: middle; }
        th { background: #f5f5f5; }
        td form { display:block; margin:4px 0; } /* stack Return + Delete */
    </style>
</head>
<body>

    <!-- Header -->
    <header>
        <div class="logo">TapNBorrow</div>
        <nav>
            <a href="/">Home</a>
            <a href="/borrow">Borrow</a>
            <a href="{{ route('nfc.inventory') }}">Inventory</a>
        </nav>
    </header>

    <h2>Borrow System</h2>

    <div class="wrap">
        <!-- Left: Inventory Table -->
        <div class="card inventory-card" style="flex:0.4;">
            <h3 style="margin:6px 0 12px;">Inventory (Reference)</h3>
            <table>
                <thead>
                    <tr>
                        <th>UID</th>
                        <th>Asset ID</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Purchase Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                    <tr>
                        <td>{{ $item->uid }}</td>
                        <td>{{ $item->asset_id }}</td>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->status }}</td>
                        <td>{{ $item->purchase_date }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5">No items yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Right: Borrow Form + Log -->
        <div class="card borrow-card" style="flex:0.6;">
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
                    <input name="user_id" type="text" placeholder="Student / Staff ID" required>
                    <input name="borrower_name" type="text" placeholder="Borrower Name" required>
                </div>

                <div class="row" style="margin-top:10px;">
                    <input id="uid" name="uid" type="text" placeholder="UID" required>
                    <input id="asset_id" name="asset_id" type="text" placeholder="Asset ID" readonly>
                    <input id="name" name="name" type="text" placeholder="Item Name" readonly>
                </div>

                <div class="row" style="margin-top:10px;">
                    <input id="status" name="status" type="text" placeholder="Status" readonly>
                    <input id="purchase_date" name="purchase_date" type="text" placeholder="Purchase Date" readonly>
                </div>

                <div class="row" style="margin-top:10px;">
                    <input name="borrow_date" type="date" placeholder="Borrow Date">
                    <input name="due_date" type="date" placeholder="Return Date">
                </div>

                <div class="row" style="margin-top:10px;">
                    <textarea name="remarks" placeholder="Remarks / Notes"></textarea>
                </div>

                <div class="actions">
                    <button type="submit" class="btn btn-success">Save Borrow</button>
                    <button type="reset" class="btn btn-danger">Clear</button>
                </div>
            </form>

            <!-- Borrow Log -->
            <h3 style="margin:20px 0 12px;">Recent Borrows</h3>
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Borrower Name</th>
                        <th>UID</th>
                        <th>Asset</th>
                        <th>Borrow Date</th>
                        <th>Return Date</th>
                        <th>Borrowed At</th>
                        <th>Returned At</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($borrows as $borrow)
                    <tr>
                        <td>{{ $borrow->user_id }}</td>
                        <td>{{ $borrow->borrower_name ?? '-' }}</td>
                        <td>{{ $borrow->uid }}</td>
                        <td>{{ $borrow->item->asset_id ?? '-' }}</td>
                        <td>{{ $borrow->borrow_date ? \Carbon\Carbon::parse($borrow->borrow_date)->format('Y-m-d') : '-' }}</td>
                        <td>{{ $borrow->return_date ? \Carbon\Carbon::parse($borrow->return_date)->format('Y-m-d') : '-' }}</td>
                        <td>{{ $borrow->borrowed_at }}</td>
                        <td>{{ $borrow->returned_at ? \Carbon\Carbon::parse($borrow->returned_at)->format('Y-m-d') : 'Not returned' }}</td>
                        <td>{{ $borrow->item->status ?? '-' }}</td>
                        <td>{{ $borrow->remarks ?? '-' }}</td>
                        <td>
                            @if(!$borrow->returned_at)
                                <form method="POST" action="{{ route('borrow.return', $borrow->uid) }}">
                                    @csrf
                                    <button class="btn btn-warning btn-sm">Return</button>
                                </form>
                            @else
                                <button class="btn btn-success btn-sm" disabled>Returned</button>
                            @endif

                            <form method="POST" action="{{ route('borrow.destroy', $borrow->id) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm" onclick="return confirm('Delete this borrow record?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center">No borrow records yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

<script>
document.getElementById('uid').addEventListener('blur', async function() {
    const uid = this.value.trim();
    if (!uid) return;

    try {
        const res = await fetch(`/borrow/fetch/${uid}`);
        if (!res.ok) throw new Error('Item not found');
        const data = await res.json();

        document.getElementById('asset_id').value = data.asset_id || '';
        document.getElementById('name').value = data.name || '';
        document.getElementById('purchase_date').value = data.purchase_date || '';
        document.getElementById('status').value = data.status || '';
    } catch (err) {
        document.getElementById('asset_id').value = '';
        document.getElementById('name').value = '';
        document.getElementById('purchase_date').value = '';
        document.getElementById('status').value = '';
        alert('UID not found in items table.');
    }
});
</script>

</body>
</html>
