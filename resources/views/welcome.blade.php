<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>{{ config('app.name', 'TapNBorrow') }}</title>
  <link rel="icon" type="image/png" href="{{ asset('images/main-logo.png') }}">

  <style>
    :root{
      --bg:#0f172a;
      --card:#111827;
      --text:#f8fafc;
      --muted:#9ca3af;
      --badge-size:clamp(120px,14vw,180px);

      /* gradient palette */
      --g1:#0c4a6e;
      --g2:#1e3a8a;
      --g3:#312e81;
      --g4:#0c4a6e;
    }

    *{ box-sizing:border-box; }

    /* Base animated gradient */
    body{
      margin:0;
      font-family:system-ui,-apple-system,Segoe UI,Roboto,Inter,Arial,sans-serif;
      color:var(--text);
      background:linear-gradient(-45deg,var(--g1),var(--g2),var(--g3),var(--g4));
      background-size:450% 450%;
      background-attachment:fixed;
      animation: bg-shift 16s cubic-bezier(.55,.1,.45,.9) infinite;
      min-height:100vh;
      position:relative;
      overflow-x:hidden;
    }

    /* AURORA + GRID FX (pure CSS) */
    .fx, .fx::before, .fx::after { position:fixed; inset:0; pointer-events:none; }
    .fx{ z-index:0; }

    .fx::before{
      content:"";
      background:
        radial-gradient(600px 600px at var(--x1,18%) var(--y1,32%), rgba(56,189,248,.30), transparent 55%),
        radial-gradient(800px 800px at var(--x2,82%) var(--y2,58%), rgba(99,102,241,.28), transparent 60%),
        radial-gradient(700px 700px at var(--x3,55%) var(--y3,85%), rgba(34,197,94,.20), transparent 60%);
      filter: blur(20px) saturate(120%);
      mix-blend-mode: screen;
      animation:
        aurora-orbit 22s ease-in-out infinite alternate,
        aurora-drift 52s linear infinite;
    }

    .fx::after{
      content:"";
      background: conic-gradient(from var(--angle,0deg),
        rgba(255,255,255,.055), rgba(255,255,255,0) 30% 70%, rgba(255,255,255,.055) 100%);
      mix-blend-mode: overlay;
      filter: blur(40px);
      animation: spin 36s linear infinite;
      opacity:.7;
    }

    .grid{
      position:fixed; inset:0; z-index:0; pointer-events:none;
      background-image:
        radial-gradient(circle at 50% 50%, rgba(255,255,255,.04), transparent 60%),
        repeating-linear-gradient(to right, rgba(255,255,255,.06) 0 1px, transparent 1px 120px),
        repeating-linear-gradient(to bottom, rgba(255,255,255,.06) 0 1px, transparent 1px 120px);
      opacity:.18;
      transform: translateZ(0);
      animation: pan 28s ease-in-out infinite alternate;
      mix-blend-mode: soft-light;
    }

    /* Vignette */
    body::after{
      content:"";
      position:fixed; inset:0; z-index:0; pointer-events:none;
      background:
        radial-gradient(1200px 600px at 50% 10%, transparent 0 60%, rgba(0,0,0,.28) 100%),
        radial-gradient(800px 400px at 20% 90%, rgba(0,0,0,.20), transparent 60%),
        linear-gradient(to bottom, rgba(0,0,0,.18), rgba(0,0,0,.24));
      mix-blend-mode:multiply;
    }

    /* Motion keyframes */
    @keyframes bg-shift{
      0%{background-position:0% 50%}
      33%{background-position:100% 50%}
      66%{background-position:50% 100%}
      100%{background-position:0% 50%}
    }
    @keyframes spin{ to{ --angle:360deg; } }
    @keyframes pan{
      0%{ transform:translate3d(-2%, -2%, 0) scale(1); }
      100%{ transform:translate3d(2%, 2%, 0) scale(1.02); }
    }
    @keyframes aurora-orbit{
      0%  { --x1:18%; --y1:32%; --x2:82%; --y2:58%; --x3:55%; --y3:85%; }
      50% { --x1:25%; --y1:25%; --x2:76%; --y2:65%; --x3:48%; --y3:78%; }
      100%{ --x1:15%; --y1:40%; --x2:88%; --y2:52%; --x3:60%; --y3:88%; }
    }
    @keyframes aurora-drift{
      0%{ transform:translate3d(0,0,0) }
      100%{ transform:translate3d(0,-3%,0) }
    }

    /* Reduced-motion */
    @media (prefers-reduced-motion: reduce){
      body{ animation:none; background-size:100% 100%; }
      .fx, .grid{ animation:none; }
    }

    /* Layout */
    .wrap{ min-height:100vh; display:grid; place-items:center; padding:24px; position:relative; z-index:1; }

    .card{
      width:100%; max-width:720px; background:var(--card); border:1px solid #1f2937;
      border-radius:20px; padding:32px;
      padding-right:calc(32px + var(--badge-size));
      box-shadow:0 10px 30px rgba(0,0,0,.35);
      display:flex; flex-direction:column; align-items:center; gap:16px; text-align:center;
      position:relative; z-index:2;
      backdrop-filter: blur(6px) saturate(110%);
    }

    .logo-right{
      position:absolute; right:16px; top:50%; transform:translateY(-50%);
      height:var(--badge-size); width:auto; object-fit:contain; pointer-events:none;
    }

    @media (max-width:640px){
      :root{ --badge-size:96px; }
      .card{ padding-right:32px; }
      .logo-right{ right:12px; }
    }

    p{ margin:0 0 16px; color:var(--muted); max-width:60ch; }

    /* ---------- Modern Buttons ---------- */
    .actions{ display:flex; gap:12px; justify-content:center; flex-wrap:wrap; }

    .actions a.button,
    .btn.btn-primary{
      --radius: 14px;
      --fill: #2563eb;                 /* primary fill */
      --glow: rgba(37,99,235,.35);     /* hover glow */
      --border1: rgba(99,102,241,.85); /* indigo */
      --border2: rgba(56,189,248,.85); /* sky   */

      position: relative;
      display: inline-block;
      padding: 12px 18px;
      border-radius: var(--radius);
      font-weight: 600;
      text-decoration: none;
      border: 1px solid transparent;
      background:
        linear-gradient(var(--card), var(--card)) padding-box,
        linear-gradient(135deg, var(--border1), var(--border2)) border-box;
      color: var(--text);
      transition: transform .15s ease, filter .15s ease, box-shadow .2s ease, background .2s ease;
    }

    /* Filled primary (Guest + Register) */
    .actions a.button:not(.ghost),
    .btn.btn-primary{
      color: #fff;
      background:
        linear-gradient(var(--fill), var(--fill)) padding-box,
        linear-gradient(135deg, var(--border1), var(--border2)) border-box;
    }

    /* Ghost outline (Login) */
    .actions a.button.ghost{
      color: var(--text);
    }

    /* Hover / Active */
    .actions a.button:hover,
    .btn.btn-primary:hover{
      transform: translateY(-1px);
      filter: brightness(1.05);
      box-shadow: 0 8px 24px var(--glow);
    }
    .actions a.button:active,
    .btn.btn-primary:active{
      transform: translateY(0);
      filter: none;
    }

    /* Focus ring */
    .actions a.button:focus-visible,
    .btn.btn-primary:focus-visible{
      outline: none;
      box-shadow:
        0 0 0 2px rgba(255,255,255,.15),
        0 0 0 4px rgba(99,102,241,.45);
    }

    /* Slightly rounder on small screens */
    @media (max-width: 640px){
      .actions a.button,
      .btn.btn-primary{ --radius: 16px; }
    }

    .text-center{ text-align:center; }
    .mt-3{ margin-top:12px; }
  </style>
</head>
<body>
  <!-- Background effects -->
  <div class="fx" aria-hidden="true"></div>
  <div class="grid" aria-hidden="true"></div>

  <main class="wrap">
    <section class="card">
      <img src="{{ asset('images/main3-logo.png') }}" alt="TapNBorrow logo" class="logo-right">

      <p>NFC inventory system powered by Laravel. Manage your assets or start scanning with your device.</p>

      <div class="actions">
        <a class="button" href="{{ route('borrow.index') }}">Guest</a>
        @guest
          <a class="button ghost" href="{{ route('login') }}">Login</a>
        @endguest
      </div>

    </section>
  </main>
</body>
</html>
