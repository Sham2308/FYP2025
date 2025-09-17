<!DOCTYPE html>
<html>
<head>
    <title>NFC Inventory Dashboard</title>
    <link rel="icon" type="image/png" href="{{ asset('pblogo (2).png') }}">
    <style>
        body { margin:0; font-family: system-ui, Arial, sans-serif; background:#fff; color:#111; }
        header { display:flex; justify-content:space-between; align-items:center;
                 padding:14px 30px; background:#2563eb; color:#fff; }
        header .logo { font-size:20px; font-weight:700; letter-spacing:0.5px; }
        header nav a { color:#fff; text-decoration:none; margin-left:20px; font-weight:600; }
        header nav a:hover { text-decoration:underline; }
        h2 { text-align:center; margin:24px 0 6px; }
        .wrap { width:95%; margin: 0 auto 40px; }
        .card { border:1px solid #e5e7eb; border-radius:8px; padding:12px 16px; margin-top:12px; background:#ffffff; }
        .alert { padding:10px 12px; border-radius:6px; margin:10px auto; width:95%; }
        .alert-success { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
        .alert-error { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
        table { border-collapse: collapse; width: 100%; margin-top:14px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #f5f5f5; }
        .badge { padding:4px 10px; border-radius:999px; font-weight:600; display:inline-block; }
        .badge-good { background:#dcfce7; color:#166534; border:1px solid #86efac; }
        .badge-na   { background:#e5e7eb; color:#374151; border:1px solid #d1d5db; }
        .top-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:8px; }
        .btn { padding:8px 14px; border:none; border-radius:8px; font-weight:600; cursor:pointer; }
        .btn-green { background:#16a34a; color:#fff; }
        .btn-red { background:#dc2626; color:#fff; }
        .btn-red:hover { background:#b91c1c; }
        .form-row { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px; }
        .form-row input, .form-row select { flex:1; min-width:160px; padding:8px; border:1px solid #cbd5e1; border-radius:6px; }
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; }
        .modal-content { background:#fff; padding:20px; border-radius:8px; width:90%; max-width:800px; }
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
            <a href="{{ route('history.index') }}">History</a>
        </nav>
    </header>

    <h2>NFC Inventory Dashboard</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    <div class="wrap">

        <!-- Top actions -->
        <div class="card">
            <div class="top-actions">
                <a href="{{ route('items.import.google') }}" class="btn btn-green">Import from Google Sheets</a>
                <button id="openModal" class="btn btn-green">+ Add Item</button>
            </div>
        </div>

        <!-- Add Item Modal -->
        <div id="itemModal" class="modal">
            <div class="modal-content">
                <h3>Add New Item</h3>
                <form method="POST" action="{{ route('items.store') }}">
                    @csrf
                    <div class="form-row">
                        <div style="flex:1; display:flex; gap:6px;">
                            <input type="text" id="uid" name="uid" placeholder="UID" required readonly>
                            <button type="button" id="scan-btn" class="btn btn-green">Scan Sticker</button>
                        </div>
                        <input type="text" name="asset_id" placeholder="Asset ID" required>
                        <input type="text" name="name" placeholder="Name">
                        <input type="text" name="detail" placeholder="Detail">
                    </div>
                    <div class="form-row">
                        <input type="text" name="accessories" placeholder="Accessories">
                        <input type="text" name="type_id" placeholder="Type ID">
                        <input type="text" name="serial_no" placeholder="Serial No">
                    </div>
                    <div class="form-row">
                        <input type="date" name="purchase_date">
                        <input type="text" name="remarks" placeholder="Remarks">
                        <select name="status">
                            <option value="available">Available</option>
                            <option value="borrowed">Borrowed</option>
                            <option value="under_repair">Under Repair</option>
                            <option value="stolen">Stolen</option>
                            <option value="missing_lost">Missing/Lost</option>
                        </select>
                    </div>
                    <div class="form-row" style="justify-content:flex-end;">
                        <button type="button" id="closeModal" class="btn btn-red">Cancel</button>
                        <button type="submit" class="btn btn-green">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Inventory Items Table -->
        <h3 style="margin-top:20px;">Inventory Items</h3>
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
                    <th>Purchase Date</th>
                    <th>Remarks</th>
                    <th>Action</th>
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
                        <td>
                            @if($item->status === 'available')
                                <span class="badge badge-good">available</span>
                            @else
                                <span class="badge badge-na">{{ $item->status ?? '—' }}</span>
                            @endif
                        </td>
                        <td>{{ $item->purchase_date ?? '—' }}</td>
                        <td>{{ $item->remarks ?? '—' }}</td>
                        <td>
                            <form method="POST" action="{{ route('items.destroy', $item->asset_id) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-red" onclick="return confirm('Delete this item?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="12">No items yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

<script>
document.getElementById("openModal").onclick = () => {
    document.getElementById("itemModal").style.display = "flex";
};
document.getElementById("closeModal").onclick = () => {
    document.getElementById("itemModal").style.display = "none";
};
window.onclick = (e) => {
    if (e.target.id === "itemModal") {
        document.getElementById("itemModal").style.display = "none";
    }
};
document.getElementById("scan-btn").addEventListener("click", async () => {
    try {
        // Step 1: Ask backend to request scan
        await fetch("/api/request-scan", { method: "POST" });

        alert("Please tap your NFC card...");

        // Step 2: Poll for UID (max 15s)
        let uid = null;
        for (let i = 0; i < 15; i++) {
            let response = await fetch("/api/read-uid");
            let data = await response.json();
            if (data.uid) {
                uid = data.uid;
                break;
            }
            await new Promise(r => setTimeout(r, 1000));
        }

        if (uid) {
            document.getElementById("uid").value = uid;
        } else {
            alert("No UID received. Try again.");
        }
    } catch (err) {
        alert("Error: " + err);
    }
});
</script>

</body>
</html>
