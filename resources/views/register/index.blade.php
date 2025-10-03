<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Favicon: public/images/main2-logo.png --}}
    @php
        $fav = asset('images/main2-logo.png');
        $ver = file_exists(public_path('images/main2-logo.png')) ? filemtime(public_path('images/main2-logo.png')) : time();
    @endphp
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $fav }}?v={{ $ver }}">
    <link rel="shortcut icon" href="{{ $fav }}?v={{ $ver }}">
    <link rel="apple-touch-icon" href="{{ $fav }}?v={{ $ver }}">
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
                <!-- 🔹 Button triggers startScan() -->
                <button type="button" class="btn btn-outline-primary" onclick="startScan()">Scan Card</button>
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
// 🔹 Called when Scan Card button is clicked
function startScan() {
    console.log("Scan button clicked"); // check console
    fetch("/api/start-scan", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        }
    })
    .then(res => res.json())
    .then(data => {
        console.log("Scan started:", data);
    })
    .catch(err => console.error("Error:", err));
}

// 🔹 Poll Laravel every second to see if UID arrived
let uidFilled = false;
setInterval(() => {
    if (uidFilled) return; // stop once filled
    fetch("/api/get-uid")
        .then(res => res.json())
        .then(data => {
            console.log("UID poll:", data); // debug
            if (data.uid) {
                document.getElementById("uid").value = data.uid;
                uidFilled = true;
            }
        })
        .catch(err => console.error("Poll error:", err));
}, 1000);
</script>

</body>
</html>
