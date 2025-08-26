<!DOCTYPE html>
<html>
<head>
    <title>NFC Scan</title>
    <style>
        body { margin:0; font-family: system-ui, Arial, sans-serif; background:#ffffff; color:#111; }
        h2 { text-align:center; margin:24px 0 8px; }
        .links { text-align:center; margin-bottom:12px; }
        .links a { color:#2563eb; text-decoration:none; font-weight:600; margin:0 6px; }
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
        .badge { padding:4px 10px; border-radius:999px; font-weight:600; display:inline-block; }
        .badge-good { background:#dcfce7; color:#166534; border:1px solid #86efac; }
        .badge-bad  { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
        .badge-na   { background:#e5e7eb; color:#374151; border:1px solid #d1d5db; }
    </style>
</head>
<body>

    <h2>NFC Scan Records</h2>
    <div class="links">
        <a href="/">← Back to Home</a> |
        <a href="{{ route('nfc.inventory') }}">→ Go to Inventory Dashboard</a>
    </div>

    <!-- Manual Register -->
    <div class="controls">
        <div class="section-title">Manual Register</div>
        <div class="row">
            <input id="asset_id" type="text" placeholder="Asset ID (e.g. CAM001)" />
            <input id="name" type="text" placeholder="Name" />
            <input id="detail" type="text" placeholder="Detail" />
            <input id="accessories" type="text" placeholder="Accessories" />
            <input id="type_id" type="text" placeholder="Type ID" />
            <input id="serial_no" type="text" placeholder="Serial No" />
            <input id="location_id" type="text" placeholder="Location ID" />
            <input id="purchase_date" type="date" />
            <input id="remarks" type="text" placeholder="Remarks" />
            <select id="status">
                <option value="">Status…</option>
                <option value="good">Good</option>
                <option value="bad">Bad</option>
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
            <button class="btn-scan" onclick="scanDevice()">Scan (device)</button>
        </div>
        <div id="scan-fields" style="display:none;">
            <div class="row">
                <input id="scan_uid" type="text" placeholder="UID (from card)" readonly />
            </div>
            <div class="row">
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
                    <option value="good">Good</option>
                    <option value="bad">Bad</option>
                </select>
            </div>
            <div class="row">
                <button class="btn-save" onclick="saveScan()">Save to table</button>
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
                <td>{{ $scan->purchase_date ? $scan->purchase_date->format('Y-m-d') : '—' }}</td>
                <td>{{ $scan->remarks ?? '—' }}</td>
                <td>
                    <button class="delete-btn" onclick="deleteRecord({{ $scan->id }}, '{{ $scan->uid }}')">Delete</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

<script>
function val(id, fallback = "") {
    const el = document.getElementById(id);
    const v = (el?.value || "").trim();
    return v.length ? v : fallback;
}

async function registerManual() {
    const payload = {
        asset_id: val("asset_id"),
        name: val("name"),
        detail: val("detail"),
        accessories: val("accessories"),
        type_id: val("type_id"),
        serial_no: val("serial_no"),
        location_id: val("location_id"),
        purchase_date: val("purchase_date"),
        remarks: val("remarks"),
        status: val("status"),
    };

    const res = await fetch("/api/nfc-register", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
    });

    document.getElementById("result").innerText = await res.text();
    location.reload();
}

async function scanDevice() {
    const res = await fetch("/api/scan-request", { method: "POST" });
    const data = await res.json();
    const reqId = data.request_id;

    document.getElementById("result").innerText =
        "Scan request created (ID " + reqId + "). Waiting for device…";

    const interval = setInterval(async () => {
        const r = await fetch("/api/scan-result/" + reqId);
        const j = await r.json();
        if (j.status === "done") {
            clearInterval(interval);
            document.getElementById("result").innerText =
                "Scan completed: " + JSON.stringify(j, null, 2);
            document.getElementById("scan-fields").style.display = "block";
            if (j.result && j.result.uid) {
                document.getElementById("scan_uid").value = j.result.uid;
            }
        }
    }, 2000);
}

async function saveScan() {
    const payload = {
        uid: val("scan_uid"),
        asset_id: val("scan_asset_id"),
        name: val("scan_name"),
        detail: val("scan_detail"),
        accessories: val("scan_accessories"),
        type_id: val("scan_type_id"),
        serial_no: val("scan_serial_no"),
        location_id: val("scan_location_id"),
        purchase_date: val("scan_purchase_date"),
        remarks: val("scan_remarks"),
        status: val("scan_status"),
    };

    const res = await fetch("/api/nfc-scan", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
    });

    document.getElementById("result").innerText = await res.text();
    location.reload();
}

async function deleteRecord(id, uid) {
    if (!uid) { alert("No UID on this row."); return; }
    if (!confirm("Delete all records for UID " + uid + "?")) return;

    let res = await fetch("/api/nfc-delete/" + encodeURIComponent(uid), { method: "DELETE" });
    let text = await res.text();
    alert("Server response: " + text);
    document.getElementById("row-" + id)?.remove();
}
</script>

</body>
</html>
