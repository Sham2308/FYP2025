<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Register User • TapNBorrow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" href="{{ asset('images/main-logo.png') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    :root{ --brand:#3b82f6; --ok:#10b981; --text:#0f172a; --border:#e5e7eb; }
    *{ box-sizing:border-box }
    body{
      margin:0; font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; color:var(--text);
      min-height:100vh; display:grid; place-items:center;
      background: radial-gradient(1200px 800px at 20% 20%, #0b4ea2, #072d63 60%, #05214a);
    }
    .card{
      width:min(720px, 92vw);
      background:#fff; border-radius:20px;
      box-shadow:0 25px 60px rgba(0,0,0,.25);
      padding:40px 44px;
    }
    h1{ margin:0 0 24px; font-size:42px; font-weight:800; color:var(--brand); letter-spacing:.2px }
    label{ display:block; font-size:15px; font-weight:700; margin:18px 0 8px; color:#1e3a8a }
    .input{
      width:100%; height:48px; border:1.5px solid var(--border); border-radius:12px;
      padding:0 16px; font-size:16px; outline:none; transition:border .2s, box-shadow .2s;
      background:#fff;
    }
    .input:focus{ border-color:var(--brand); box-shadow:0 0 0 4px rgba(59,130,246,.15) }
    .uid-row{ display:flex; gap:0 }
    .uid-row .input{ border-radius:12px 0 0 12px; border-right:none }
    .btn{
      height:48px; padding:0 18px; border:none; border-radius:12px; font-weight:700; font-size:16px; cursor:pointer;
    }
    .btn-scan{ background:var(--brand); color:#fff; border-radius:0 12px 12px 0 }
    .btn-primary{ background:var(--ok); color:#fff }
    .btn-secondary{ background:#6b7280; color:#fff }
    .actions{ display:flex; gap:12px; margin-top:26px }
    .error-box{
      background:#fef2f2; border:1px solid #fecaca; color:#b91c1c;
      padding:12px 14px; border-radius:12px; margin-bottom:10px; font-size:14px
    }
    .field-error{ color:#dc2626; font-size:13px; margin-top:6px }
    @media (max-width:560px){
      .uid-row{ flex-direction:column }
      .uid-row .input{ border-right:1.5px solid var(--border); border-radius:12px; margin-bottom:10px }
      .btn-scan{ border-radius:12px }
      .actions{ flex-direction:column }
    }
  </style>
</head>
<body>
  <main class="card">
    <h1>Register User</h1>

    @if ($errors->any())
      <div class="error-box"><strong>Please fix the errors below.</strong></div>
    @endif

    <form method="POST" action="{{ route('public.register.store') }}">
      @csrf

      <label for="uid">UID (Scanned)</label>
      <div class="uid-row">
        <input id="uid" name="uid" type="text" class="input" placeholder="Scan card or enter UID"
               value="{{ old('uid') }}" required>
        <button type="button" class="btn btn-scan" onclick="handleScanClick()">Scan Card</button>
      </div>
      @error('uid') <div class="field-error">{{ $message }}</div> @enderror

      <label for="name">Name</label>
      <input id="name" name="name" type="text" class="input" placeholder="Enter your name"
             value="{{ old('name') }}" required>
      @error('name') <div class="field-error">{{ $message }}</div> @enderror

      <label for="email">Email</label>
      <input id="email" name="email" type="email" class="input" placeholder="Enter your email"
             value="{{ old('email') }}" required>
      @error('email') <div class="field-error">{{ $message }}</div> @enderror

      <label for="student_or_staff_id">Student / Staff ID</label>
      <input id="student_or_staff_id" name="student_or_staff_id" type="text" class="input"
             placeholder="Your ID" value="{{ old('student_or_staff_id') }}" required>
      @error('student_or_staff_id') <div class="field-error">{{ $message }}</div> @enderror

      <div class="actions">
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="{{ url('/borrow') }}" class="btn btn-secondary" style="display:inline-flex;align-items:center;justify-content:center">Cancel</a>
      </div>
    </form>
  </main>

  <script>
    function handleScanClick(){ document.getElementById('uid').focus(); }
  </script>
</body>
</html>
