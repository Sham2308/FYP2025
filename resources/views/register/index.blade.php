<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register User</title>
    <link rel="icon" type="image/png" href="{{ asset('pblogo (2).png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-sm p-4">
        <h3 class="mb-4 fw-semibold text-primary">Register User</h3>

        <form action="{{ route('register-user.store') }}" method="POST">
            @csrf

            <!-- UID -->
            <div class="mb-3">
                <label for="uid" class="form-label fw-semibold">UID (Scanned)</label>
                <div class="input-group">
                    <input type="text" id="uid" name="uid" class="form-control"
                        value="" readonly required>
                    <button type="button" class="btn btn-outline-primary" onclick="startRegisterScan()">Scan Card</button>
                </div>
                <div id="scanStatus" class="form-text text-muted mt-1"></div>
            </div>

            <!-- Name -->
            <div class="mb-3">
                <label for="name" class="form-label fw-semibold">Name</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Enter full name" required>
            </div>

            <!-- Student ID -->
            <div class="mb-3">
                <label for="student_id" class="form-label fw-semibold">Student / Staff ID</label>
                <input type="text" id="student_id" name="student_id" class="form-control" placeholder="e.g. 23FTT123" required>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-success">Save</button>
                <a href="/nfc-inventory" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
// 🔹 Start register scan — same logic as Project 1
async function startRegisterScan() {
    console.log("🔹 Register Scan initiated");
    document.getElementById("scanStatus").textContent = "Waiting for card tap...";

    // Tell Laravel & ESP32 to prepare register scan
    await fetch("/api/request-register-scan", {
        method: "POST",
        headers: { "Content-Type": "application/json" }
    });

    let uid = null;

    // 🔁 Poll up to 30 s for UID
    for (let i = 0; i < 30; i++) {
        await new Promise(r => setTimeout(r, 1000));
        try {
            const res = await fetch("/api/read-register-uid");
            const data = await res.json();
            console.log("📡 Poll:", data);
            if (data.uid) {
                uid = data.uid;
                break;
            }
        } catch (err) {
            console.error("Poll error:", err);
        }
    }

    const status = document.getElementById("scanStatus");
    if (!uid) {
        status.textContent = "❌ No card detected. Try again.";
        status.classList.add("text-danger");
    } else {
        document.getElementById("uid").value = uid;
        status.textContent = `✅ Card detected: ${uid}`;
        status.classList.remove("text-danger");
        status.classList.add("text-success");
    }
}
</script>

</body>
</html>
