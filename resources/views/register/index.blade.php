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
    <h2>Register User</h2>

    <form action="{{ route('register-user.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="uid" class="form-label">UID (Scanned)</label>
            <div class="input-group">
                <input type="text" id="uid" name="uid" class="form-control"
                       value="{{ $uid ?? '' }}" readonly required>
                <!-- 🔹 Button triggers startRegisterScan() -->
                <button type="button" class="btn btn-outline-primary" onclick="startRegisterScan()">Scan Card</button>
            </div>
        </div>

        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" id="name" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="student_id" class="form-label">Student / Staff ID</label>
            <input type="text" id="student_id" name="student_id" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-success">Save</button>
        <a href="/nfc-inventory" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script>
async function startRegisterScan() {
    console.log("🔹 Register Scan button clicked");

    // Tell Laravel/ESP32 to expect register scan
    await fetch("/api/request-register-scan", {
        method: "POST",
        headers: { "Content-Type": "application/json" }
    });

    alert("Please tap your card to register...");

    let uid = null;
    // 🔹 Poll up to 30s (30 attempts, 1s apart)
    for (let i = 0; i < 30; i++) {
        await new Promise(r => setTimeout(r, 1000));
        try {
            const res = await fetch("/api/read-register-uid");
            const data = await res.json();
            console.log("📡 Register UID poll:", data);
            if (data.uid) {
                uid = data.uid;
                break;
            }
        } catch (err) {
            console.error("Poll error:", err);
        }
    }

    if (!uid) {
        alert("❌ No card detected, please try again.");
    } else {
        console.log("✅ UID detected:", uid);
        document.getElementById("uid").value = uid;
    }
}
</script>

</body>
</html>
