<!DOCTYPE html>
<html>
<head>
    <title>NFC Scan</title>
    <style>
        body { margin:0; font-family: system-ui, Arial, sans-serif; background:#ffffff; color:#111; }
        header { display:flex; justify-content:space-between; align-items:center;
                 padding:14px 30px; background:#2563eb; color:#fff; }
        header .logo { font-size:20px; font-weight:700; letter-spacing:0.5px; }
        header nav a { color:#fff; text-decoration:none; margin-left:20px; font-weight:600; }
        header nav a:hover { text-decoration:underline; }
        h2 { text-align:center; margin:24px 0 8px; }
        .controls { max-width: 1100px; margin: 0 auto 18px; padding: 16px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb; }
        .section-title { font-weight:600; margin-bottom:10px; text-align:center; }
        .row { display:flex; gap:12px; flex-wrap:wrap; justify-content:center; margin-bottom:12px; }
        .row input, .row select { padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px; min-width:160px; }
        .row button { padding:10px 14px; border:none; border-radius:8px; font-weight:600; cursor:pointer; }
        .btn-register { background:#16a34a; color:white; }
        .btn-scan { background:#2563eb; color:white; }
        .btn-save { background:#9333ea; color:white; }
        .result { max-width:1100px; margin:0 auto 18px; padding:10px 12px; border:1px solid #e5e7eb; border-radius:8px; background:#fff; font-family: monospace; color:#111827; white-space:pre-wrap; }
        table { border-collapse: collapse; width: 95%; margin: 12px auto 40px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #f5f5f5; }
        .delete-btn { padding:6px 12px; border:none; border-radius:6px; background:#dc2626; color:white; cursor:pointer; }
        .delete-btn:hover { background:#b91c1c; }
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

    <h2>NFC Scan Records</h2>

    <!-- Manual Register -->
    <div class="controls">
        <div class="section-title">Manual Register</div>
        <div class="row">
            <input id="manual_uid" type="text" placeholder="UID (manual entry)" />
            <input id="manual_asset_id" type="text" placeholder="Asset ID" />
            <input id="manual_name" type="text" placeholder="Name" />
            <input id="manual_detail" type="text" placeholder="Detail" />
            <input id="manual_accessories" type="text" placeholder="Accessories" />
            <input id="manual_type_id" type="text" placeholder="Type ID" />
            <input id="manual_serial_no" type="text" placeholder="Serial No" />
            <input id="manual_location_id" type="text" placeholder="Location ID" />
            <input id="manual_purchase_date" type="date" />
            <input id="manual_remarks" type="text" placeholder="Remarks" />
            <select id="manual_status">
                <option value="">Status…</option>
                <option value="available">Available</option>
                <option value="borrowed">Borrowed</option>
                <option value="under_repair">Under Repair</option>
                <option value="stolen">Stolen</option>
                <option value="missing_lost">Missing/Lost</option>
            </select>
        </div>
        <div class="row">
            <button class="btn-register" onclick="registerManual()">Register (manual)</button>
        </div>
    </div>

    <!-- Device Scan -->
    <div class="controls">
        <div class="section-title">Scan using Device</div>
        <div class="row">
            <button class="btn-scan" onclick="scanDevice()">Scan NFC Sticker</button>
        </div>
        <div id="scan-fields" style="display:none;">
            <div class="row">
                <input id="scan_uid" type="text" placeholder="UID (from device)" readonly />
                <input id="scan_asset_id" type="text" placeholder="Asset ID" />
                <input id="scan_name" type="text" placeholder="Name" />
                <input id="scan_detail" type="text" placeholder="Detail" />
                <input id="scan_accessories" type="text" placeholder="Accessories" />
                <input id="scan_type_id" type="text" placeholder="Type ID" />
                <input id="scan_serial_no" type="text" placeholder="Serial No" />
                <input id="scan_location_id" type="text" placeholder="Location ID" />
                <input id="scan_purchase_date" type="date" />
                <input id="scan_remarks" type="text" placeholder="Remarks" />
                <select id="scan_status">
                    <option value="">Status…</option>
                    <option value="available">Available</option>
                    <option value="borrowed">Borrowed</option>
                    <option value="under_repair">Under Repair</option>
                    <option value="stolen">Stolen</option>
                    <option value="missing_lost">Missing/Lost</option>
                </select>
            </div>
            <div class="row">
                <button class="btn-save" onclick="saveScan()">Save Scan</button>
            </div>
        </div>
    </div>

    <div id="result" class="result">Result will appear here…</div>

    <!-- Data Table -->
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
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($scans as $scan)
            <tr id="row-{{ $scan->id }}">
                <td>{{ $scan->uid }}</td>
                <td>{{ $scan->asset_id }}</td>
                <td>{{ $scan->name }}</td>
                <td>{{ $scan->detail }}</td>
                <td>{{ $scan->accessories }}</td>
                <td>{{ $scan->type_id }}</td>
                <td>{{ $scan->serial_no }}</td>
                <td>{{ $scan->location_id }}</td>
                <td>
                    @if($scan->status === 'available') <span style="color:green;">Available</span>
                    @elseif($scan->status === 'borrowed') <span style="color:orange;">Borrowed</span>
                    @elseif($scan->status === 'under_repair') <span style="color:blue;">Under Repair</span>
                    @elseif($scan->status === 'stolen') <span style="color:red;">Stolen</span>
                    @elseif($scan->status === 'missing_lost') <span style="color:darkred;">Missing/Lost</span>
                    @else {{ $scan->status }}
                    @endif
                </td>
                <td>{{ $scan->purchase_date }}</td>
                <td>{{ $scan->remarks }}</td>
                <td>
                    <button class="delete-btn" onclick="deleteRecord({{ $scan->id }}, '{{ $scan->uid }}')">Delete</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
