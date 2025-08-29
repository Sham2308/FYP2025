<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>TapNBorrow — Welcome</title>
  <style>
    :root { --bg:#0f172a; --card:#111827; --text:#f8fafc; --muted:#9ca3af; }
    * { box-sizing:border-box; }
    body { margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif; background:var(--bg); color:var(--text); }
    .wrap { min-height:100vh; display:grid; place-items:center; padding:24px; }
    .card { width:100%; max-width:720px; background:var(--card); border:1px solid #1f2937; border-radius:20px; padding:32px; box-shadow: 0 10px 30px rgba(0,0,0,.25); }
    h1 { margin:0 0 8px; font-weight:800; letter-spacing:.3px; text-align:center; }
    p { margin:0 0 24px; color:var(--muted); text-align:center; }
    .actions { display:flex; gap:12px; justify-content:center; flex-wrap:wrap; }
    a.button { text-decoration:none; display:inline-block; padding:12px 18px; border-radius:12px; border:1px solid #374151; background:#2563eb; color:white; font-weight:600; transition:.15s transform ease, .15s filter ease; }
    a.button:hover { transform:translateY(-1px); filter:brightness(1.05); }
    .ghost { background:transparent; color:var(--text); }
  </style>
</head>
<body>
  <main class="wrap">
    <section class="card">
      <h1>TapNBorrow</h1>
      <p>NFC inventory system powered by Laravel. Manage your assets or start scanning with your device.</p>
      <div class="actions">
        <a class="button" href="{{ route('nfc.inventory') }}">Go to Inventory</a>
      </div>
    </section>
  </main>
</body>
</html>
