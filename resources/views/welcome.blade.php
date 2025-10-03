<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>{{ config('app.name', 'TapNBorrow') }}</title>
  <link rel="icon" type="image/png" href="{{ asset('images/main-logo.png') }}">

  <style>
    :root { 
      --bg:#0f172a; 
      --card:#111827; 
      --text:#f8fafc; 
      --muted:#9ca3af;
      /* controls the badge size */
      --badge-size: clamp(120px, 14vw, 180px);
    }
    * { box-sizing:border-box; }
    body { margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif; background:var(--bg); color:var(--text); }
    .wrap { min-height:100vh; display:grid; place-items:center; padding:24px; }

    /* Card */
    .card {
      width:100%; max-width:720px; background:var(--card); border:1px solid #1f2937;
      border-radius:20px; 
      padding:32px;
      /* reserve space on the RIGHT for the badge so content never overlaps it */
      padding-right: calc(32px + var(--badge-size));
      box-shadow: 0 10px 30px rgba(0,0,0,.25);
      display:flex; flex-direction:column; align-items:center; gap:16px; text-align:center;
      position: relative; /* enables absolute positioning for the badge */
    }

    /* (kept) */
    .logo {
      display:block; width:auto;
      height:clamp(96px, 12vh, 160px);
      margin: 4px auto 6px;
    }

    /* Center-right badge */
    .logo-right{
      position:absolute; 
      right:16px;
      top:50%;
      transform: translateY(-50%);   /* vertical centering */
      height:var(--badge-size);
      width:auto;
      object-fit:contain;
      pointer-events:none;
    }

    /* Smaller screens: shrink badge and drop special padding */
    @media (max-width: 640px){
      :root { --badge-size: 96px; }
      .card { padding-right:32px; }
      .logo-right{ right:12px; }
    }

    p { margin:0 0 16px; color:var(--muted); max-width:60ch; }
    .actions { display:flex; gap:12px; justify-content:center; flex-wrap:wrap; }
    a.button { text-decoration:none; display:inline-block; padding:12px 18px; border-radius:12px; border:1px solid #374151; background:#2563eb; color:white; font-weight:600; transition:.15s transform ease, .15s filter ease; }
    a.button:hover { transform:translateY(-1px); filter:brightness(1.05); }
    .ghost { background:transparent; color:var(--text); }
    .text-center { text-align:center; }
    .mt-3 { margin-top:12px; }
  </style>
</head>
<body>
  <main class="wrap">
    <section class="card">
      <!-- Center-right logo -->
      <img src="{{ asset('images/main-logo.png') }}" alt="TapNBorrow logo" class="logo-right">

      <p>NFC inventory system powered by Laravel. Manage your assets or start scanning with your device.</p>

      <div class="actions">
        {{-- Everyone (guest or logged-in non-admin) can access Borrow --}}
        <a class="button" href="{{ route('borrow.index') }}">Guest</a>

        {{-- Only show login button if user is not logged in --}}
        @guest
          <a class="button ghost" href="{{ route('login') }}">Login</a>
        @endguest
      </div>

      <div class="text-center mt-3">
        <a href="{{ route('register-user') }}" class="btn btn-primary">Register</a>
      </div>
    </section>
  </main>
</body>
</html>
