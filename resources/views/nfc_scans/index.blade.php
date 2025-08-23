<!DOCTYPE html>
<html>
<head>
    <title>NFC Scans</title>
    <style>
        body { margin:0; font-family: system-ui, Arial, sans-serif; background:#ffffff; color:#111; }
        h2 { text-align:center; margin:24px 0 8px; }
        .links { text-align:center; margin-bottom:12px; }
        .links a { color:#2563eb; text-decoration:none; font-weight:600; margin:0 6px; }
        .controls { max-width: 1000px; margin: 0 auto 18px; padding: 16px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb; }
        .section-title { font-weight:600; margin-bottom:10px; text-align:center; }
        .row { display:flex; gap:12px; flex-wrap:wrap; justify-content:center; margin-bottom:12px; }
        .row input, .row select { padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px; min-width:160px; }
        .row button { padding:10px 14px; border:none; border-radius:8px; font-weight:600; cursor:pointer; }
        .btn-register { background:#16a34a; color:white; }
        .btn-scan { background:#2563eb; color:white; }
        .btn-save { background:#9333ea; color:white; }
        .result { max-width:1000px; margin:0 auto 18px; padding:10px 12px; border:1px solid #e5e7eb; border-radius:8px; background:#fff; font-family: monospace; color:#111827; white-space:pre-wrap; }
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
            <input id="student_id" type="text" placeholder="Student ID (e.g. 23FTT1234)" />
            <input id="user_name" type="text" placeholder="User name" />
            <input id="item_id" type="text" placeholder="Item ID (e.g. CAM001)" />
            <input id="item_name" type="text" placeholder="Item name" />
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
                <input id="scan_student_id" type="text" placeholder="Student ID (e.g. 23FTT1234)" />
                <input id="scan_user_name" type="text" placeholder="User name" />
                <input id="scan_item_id" type="text" placeholder="Item ID" />
                <input id="scan_item_name" type="text" placeholder="Item name" />
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
                <th>ID</th>
                <th>UID</th>
                <th>Student ID</th>
                <th>User</th>
                <th>Item ID</th>
                <th>Item</th>
                <th>Status</th>
                <th>Scanned At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($scans as $scan)
            <tr id="row-{{ $scan->id }}">
                <td>{{ $scan->id }}</td>
                <td>{{ $scan->uid ?? 'N/A' }}</td>
                <td>{{ $scan->student_id ?? 'N/A' }}</td>
                <td>{{ $scan->user_name ?? 'N/A' }}</td>
                <td>{{ $scan->item_id ?? 'N/A' }}</td>
                <td>{{ $scan->item_name ?? 'N/A' }}</td>
                <td>
                    @php $st = $scan->status; @endphp
                    @if($st === 'good')
                        <span class="badge badge-good">good</span>
                    @elseif($st === 'bad')
                        <span class="badge badge-bad">bad</span>
                    @else
                        <span class="badge badge-na">N/A</span>
                    @endif
                </td>
                <td>{{ $scan->created_at }}</td>
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

// Manual register
async function registerManual() {
    const student_id = val("student_id");
    const user_name  = val("user_name");
    const item_id    = val("item_id");
    const item_name  = val("item_name");
    const status     = val("status");

    const res = await fetch("/api/nfc-register", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ student_id, user_name, item_id, item_name, status })
    });
    document.getElementById("result").innerText = await res.text();
    location.reload();
}

// Start device scan
async function scanDevice() {
    const res = await fetch("/api/scan-request", { method: "POST" });
    const data = await res.json();
    const reqId = data.request_id;

    document.getElementById("result").innerText =
        "Scan request created (ID " + reqId + "). Waiting for device…";

    const interval = setInterval(async () => {
        const r = await fetch("/api/scan-result/" + reqId);
        const j = await r.json();
        console.log("ScanResult", j);
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

// Save scanned data
async function saveScan() {
    const uid        = val("scan_uid");
    const student_id = val("scan_student_id");
    const user_name  = val("scan_user_name");
    const item_id    = val("scan_item_id");
    const item_name  = val("scan_item_name");
    const status     = val("scan_status");

    const res = await fetch("/api/nfc-scan", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ uid, student_id, user_name, item_id, item_name, status })
    });
    document.getElementById("result").innerText = await res.text();
    location.reload();
}

// Delete by UID
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
