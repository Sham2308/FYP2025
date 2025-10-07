<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/main2-logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/main2-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/main2-logo.png') }}">

    <!-- Fonts (optional) -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Vite assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
      :root{
        --bg:#0f172a;     /* base navy */
        --g1:#0c3f76;     /* deep blue 1 */
        --g2:#0b58a4;     /* deep blue 2 */
        --g3:#0a71b6;     /* blue/cyan hint */
      }

      /* Smooth gradient background (no patterns/blobs) */
      .bg-wrap{
        position:fixed; inset:0; z-index:0; overflow:hidden;
        background:
          radial-gradient(160vmax 120vmax at 50% 110%, rgba(0,0,0,.55) 10%, transparent 55%),
          radial-gradient(140vmax 100vmax at -20% -20%, rgba(0,0,0,.45) 10%, transparent 55%),
          linear-gradient(140deg, var(--g1) 0%, var(--g2) 42%, var(--g1) 70%, #08213d 100%);
      }
      .bg-sheen{
        position:absolute; inset:-10%;
        background:
          radial-gradient(80vmax 60vmax at 120% 0%, color-mix(in oklab, var(--g3) 18%, transparent) 0 35%, transparent 60%),
          radial-gradient(70vmax 50vmax at 0% 120%, color-mix(in oklab, var(--g2) 12%, transparent) 0 35%, transparent 60%);
        filter:saturate(110%);
        animation: drift 26s ease-in-out infinite alternate;
        opacity:.55;
        pointer-events:none;
      }
      @keyframes drift{
        0%   { transform: translate3d(-2%, -2%, 0) scale(1); }
        100% { transform: translate3d(2%, 2%, 0)  scale(1.03); }
      }

      /* Global: make form fields readable on white cards */
      input, select, textarea{
        color:#111827 !important;           /* slate-900 */
        -webkit-text-fill-color:#111827;     /* Safari */
        caret-color:#111827;
        background:#ffffff;                  /* ensure white bg */
      }
      input::placeholder, textarea::placeholder{
        color:#9ca3af !important;            /* gray-400 */
        opacity:1;
      }
      input[disabled], input[readonly]{
        color:#6b7280 !important;            /* gray-500 */
        -webkit-text-fill-color:#6b7280;
      }
    </style>
</head>
<body class="font-sans antialiased bg-[#0f172a]">
    <!-- Gradient background -->
    <div class="bg-wrap"><div class="bg-sheen"></div></div>

    <!-- Foreground content -->
    <main class="relative z-10 min-h-[100svh] flex items-center justify-center p-4">
        {{ $slot }}
    </main>
</body>
</html>
