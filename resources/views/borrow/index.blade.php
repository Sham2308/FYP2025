<!DOCTYPE html>
<html>
<head>
    <title>TapNBorrow</title>
    <link rel="icon" type="image/png" href="{{ asset('images/main-logo.png') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { margin:0; font-family: system-ui, Arial, sans-serif; background:#ffffff; color:#111; font-size:14px; }
        header { display:flex; justify-content:space-between; align-items:center;
                 padding:14px 30px; background:#2563eb; color:#fff; }
        header .logo {
            display:flex;
            align-items:center;
            gap:8px;
            font-size:20px;
            font-weight:700;
            letter-spacing:0.5px;
        }
        header .logo img { height:24px; width:auto; }
        header nav a { color:#fff; text-decoration:none; margin-left:20px; font-weight:600; }
        header nav a:hover { text-decoration:underline; }
        h2 { text-align:center; margin:24px 0 8px; font-size:22px; }

        /* layout + card styles */
        .wrap { width:98%; max-width:1600px; margin: 0 auto 40px; display:flex; gap:24px; align-items:flex-start; }
        .card { border:1px solid #e5e7eb; border-radius:12px; padding:18px; background:#f9fafb; margin-top:14px; flex:1; font-size:14px; }

        .row { display:flex; gap:12px; flex-wrap:wrap; }
        .row input, .row textarea {
            padding:8px 10px; border:1px solid #cbd5e1; border-radius:8px;
            min-width:150px; flex:1; font-size:14px;
        }
        input[readonly] { background:#e5e7eb; color:#374151; }
        textarea { min-height:60px; resize:vertical; flex:1; }

        .actions { display:flex; gap:10px; margin-top:12px; }
        .btn { padding:8px 14px; border:none; border-radius:8px; font-weight:600; cursor:pointer; font-size:14px; }
        .btn-success { background:#16a34a; color:#fff; }
        .btn-danger { background:#dc2626; color:#fff; }
        .btn-warning { background:#f59e0b; color:#fff; }
        .btn-success:hover { background:#15803d; }
        .btn-danger:hover { background:#b91c1c; }
        .btn-warning:hover { background:#d97706; }
        .btn-sm { padding:5px 10px; font-size:12px; }

        table { border-collapse: collapse; width: 100%; margin-top:16px; font-size:13px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: center; vertical-align: middle; }
        th { background: #f5f5f5; font-size:13px; }
        td form { display:block; margin:4px 0; }

        .inventory-card { flex:0.35; }
        .borrow-card { flex:0.65; }
    </style>
</head>
<body>

<header>
    <div class="logo">
        <img src="{{ asset('images/icon-logo.png') }}" alt="TapNBorrow logo">
        <span>TapNBorrow</span>
    </div>

    <nav>
        <a href="/">Home</a>
        <a href="/borrow">Borrow</a>
        <a href="{{ route('reports.create') }}">Report</a>
    </nav>
</header>

<h2>Borrow System</h2>

<div class="wrap">
    <!-- Left: Inventory Table -->
    <div class="card inventory-card" style="flex:0.4;">
        <h3 style="margin:6px 0 12px;">Inventory (Reference)</h3>

        <!-- 🔹 Filter bar -->
        <div style="margin-bottom:10px; display:flex; gap:8px; align-items:center;">
            <input type="text" id="filterInput" placeholder="Search by name, asset ID, or UID"
                   style="flex:1; padding:6px 10px; border:1px solid #ccc; border-radius:6px; font-size:13px;">
            <select id="statusFilter" style="padding:6px 8px; border:1px solid #ccc; border-radius:6px; font-size:13px;">
                <option value="">All Status</option>
                <option value="available">Available</option>
                <option value="under repair">Under Repair</option>
                <option value="borrowed">Borrowed</option>
                <option value="retire">Retire</option>
                <option value="stolen">Stolen</option>
                <option value="missing/lost">Missing/Lost</option>
            </select>
        </div>

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
                <input id="card_uid" name="card_uid" type="text" placeholder="Card UID" readonly>
                <button type="button" id="scanBtn" class="btn btn-warning">Scan Card</button>
                <input id="user_id" name="user_id" type="text" placeholder="Student / Staff ID" readonly required>
                <input id="borrower_name" name="borrower_name" type="text" placeholder="Borrower Name" readonly required>
            </div>

            <div class="row" style="margin-top:10px;">
                <div style="display:flex; gap:6px; flex:1;">
                    <input id="item_uid" name="uid" type="text" placeholder="Item UID" required readonly>
                    <button type="button" id="scanStickerBtn" class="btn btn-success">Scan Sticker</button>
                </div>
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
                    <td>{{ $borrow->due_date ? \Carbon\Carbon::parse($borrow->due_date)->format('Y-m-d') : '-' }}</td>
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
                <tr><td colspan="11" class="text-center">No borrow records yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
async function fetchUser(cardUid) {
    try {
        const res = await fetch(`/api/borrow/user/${cardUid}`);
        if (!res.ok) throw new Error("User not found");
        const data = await res.json();
        document.getElementById('card_uid').value = data.uid || '';
        document.getElementById('user_id').value = data.student_id || '';
        document.getElementById('borrower_name').value = data.name || '';
    } catch (err) {
        alert(err.message);
    }
}

async function fetchItem(itemUid) {
    try {
        const res = await fetch(`/borrow/fetch/${itemUid}`);
        if (!res.ok) throw new Error("Item not found");
        const data = await res.json();
        document.getElementById('asset_id').value = data.asset_id || '';
        document.getElementById('name').value = data.name || '';
        document.getElementById('status').value = data.status || '';
        document.getElementById('purchase_date').value = data.purchase_date || '';
    } catch (err) {
        alert(err.message);
    }
}

document.getElementById('item_uid').addEventListener('blur', function() {
    const uid = this.value.trim();
    if (uid) fetchItem(uid);
});

document.getElementById('scanBtn').addEventListener('click', async () => {
    try {
        await fetch('/api/request-scan', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: 'card' })
        });
        alert("Please tap your user card...");
        let uid = null;
        for (let i = 0; i < 15; i++) {
            await new Promise(r => setTimeout(r, 1000));
            let res = await fetch('/api/read-uid');
            let data = await res.json();
            if (data.uid) { uid = data.uid; break; }
        }
        if (!uid) throw new Error("No card detected");
        document.getElementById('card_uid').value = uid;
        await fetchUser(uid);
    } catch (err) {
        alert("Error scanning card: " + err.message);
    }
});

document.getElementById('scanStickerBtn').addEventListener('click', async () => {
    try {
        await fetch('/api/request-scan', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: 'sticker' })
        });
        alert("Please tap the item’s NFC sticker...");
        let uid = null;
        for (let i = 0; i < 15; i++) {
            await new Promise(r => setTimeout(r, 1000));
            let res = await fetch('/api/read-uid');
            let data = await res.json();
            if (data.uid) { uid = data.uid; break; }
        }
        if (!uid) throw new Error("No sticker detected");
        document.getElementById('item_uid').value = uid;
        let itemRes = await fetch(`/borrow/fetch/${uid}`);
        if (!itemRes.ok) throw new Error("Item not found in DB");
        let item = await itemRes.json();
        document.getElementById('asset_id').value       = item.asset_id || '';
        document.getElementById('name').value           = item.name || '';
        document.getElementById('status').value         = item.status || '';
        document.getElementById('purchase_date').value  = item.purchase_date || '';
        document.querySelector("textarea[name='remarks']").value = item.remarks || '';
    } catch (err) {
        alert("Error scanning sticker: " + err.message);
    }
});

// 🔹 Inventory Filter (case-insensitive)
const filterInput = document.getElementById('filterInput');
const statusFilter = document.getElementById('statusFilter');
const table = document.querySelector('.inventory-card table');
const rows = table.querySelectorAll('tbody tr');

function filterTable() {
    const text = filterInput.value.trim().toLowerCase();
    const status = statusFilter.value.trim().toLowerCase();
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        const uid = cells[0]?.innerText.trim().toLowerCase() || '';
        const asset = cells[1]?.innerText.trim().toLowerCase() || '';
        const name = cells[2]?.innerText.trim().toLowerCase() || '';
        const stat = cells[3]?.innerText.trim().toLowerCase() || '';
        const matchText = !text || uid.includes(text) || asset.includes(text) || name.includes(text);
        const matchStatus = !status || stat === status;
        row.style.display = (matchText && matchStatus) ? '' : 'none';
    });
}
filterInput.addEventListener('input', filterTable);
statusFilter.addEventListener('change', filterTable);
</script>
</body>
</html>
