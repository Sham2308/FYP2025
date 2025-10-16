<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>TapNBorrow</title>
    <link rel="icon" type="image/png" href="{{ asset('images/main-logo.png') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root{
            --brand:#2563eb;     /* header blue (changed from green) */
            --bg:#f4f7f6;        /* page bg */
            --card:#ffffff;      /* card bg */
            --text:#0f172a;      /* dark text */
            --muted:#64748b;     /* muted text */
            --ring:#94a3b8;      /* input border */
            --ring-focus:#2563eb;
        }
        *{box-sizing:border-box}
        body{margin:0;font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);color:var(--text)}
        header{background:var(--brand);color:#fff;display:flex;justify-content:space-between;align-items:center;padding:14px 24px}
        header .brand{display:flex;align-items:center;gap:10px;font-weight:700;letter-spacing:.3px} /* made flex to hold logo + text */
        header .brand img{height:24px;width:auto;display:block} /* logo beside text */
        header nav a{color:#fff;text-decoration:none;margin-left:18px;font-weight:600;opacity:.95}
        header nav a:hover{opacity:1;text-decoration:underline}
        .wrap{max-width:820px;margin:36px auto;padding:0 16px}
        h1{font-size:28px;margin:0 0 16px;text-align:left}
        .card{background:var(--card);border:1px solid #e5e7eb;border-radius:14px;padding:18px 18px 14px;box-shadow:0 8px 24px rgba(0,0,0,.06)}
        .card h2{font-size:18px;margin:0 0 4px}
        .card p.lead{margin:0 0 14px;color:var(--muted);font-size:14px}
        .row{display:flex;gap:12px;flex-wrap:wrap;margin-top:10px}
        label{display:block;font-size:13px;font-weight:600;margin:12px 0 6px}
        input[type=text],input[type=email],select,textarea{
            width:100%;padding:10px 12px;border:1px solid var(--ring);border-radius:10px;font-size:14px;background:#fff;outline:none
        }
        input[type=file]{border:1px dashed var(--ring);padding:10px;border-radius:10px;background:#fff;width:100%}
        textarea{min-height:130px;resize:vertical}
        input:focus,select:focus,textarea:focus{border-color:var(--ring-focus);box-shadow:0 0 0 3px rgba(37,99,235,.15)}
        .actions{display:flex;gap:12px;align-items:center;margin-top:14px}
        .btn{appearance:none;border:none;border-radius:10px;padding:10px 14px;font-weight:700;cursor:pointer}
        .btn-primary{background:#166534;color:#fff}
        .btn-primary:hover{background:#14532d}
        .btn-link{background:transparent;color:var(--muted);text-decoration:underline;padding:0 4px}
        .alert{padding:10px 12px;border-radius:10px;margin:12px 0;font-size:14px}
        .alert-success{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46}
        .alert-error{background:#fee2e2;border:1px solid #fecaca;color:#991b1b}
        small.helper{color:var(--muted);display:block;margin-top:6px}
        .two-col{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        @media (max-width:640px){.two-col{grid-template-columns:1fr}}
    </style>
</head>
<body>
<header>
    <div class="brand">
        <img src="{{ asset('images/icon-logo.png') }}" alt="TapNBorrow logo">
        <span>TapNBorrow</span>
    </div>
    <nav>
        <a href="/">Home</a>
        <a href="/borrow">Borrow</a>
        <a href="{{ route('reports.create') }}">Report</a>
    </nav>
</header>

<div class="wrap">
    <h1>Report an Issue</h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error">
            <ul style="margin:0 0 0 16px;">
                @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <h2>Report an Issue</h2>
        <p class="lead">Let us know about any bugs or issues you’ve encountered.</p>

        <form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data">
            @csrf

            {{-- Guest-only (since you allow public reports) --}}
            @guest
            <div class="two-col">
                <div>
                    <label for="guest_name">Your Name</label>
                    <input id="guest_name" type="text" name="guest_name" value="{{ old('guest_name') }}" required>
                </div>
                <div>
                    <label for="guest_email">Your Email</label>
                    <input id="guest_email" type="email" name="guest_email" value="{{ old('guest_email') }}" required>
                </div>
            </div>
            @endguest

            <label for="subject">Issue Title</label>
            <input id="subject" type="text" name="subject" placeholder="e.g., Wrong status shown for item"
                   value="{{ old('subject') }}" required>

            <div class="two-col">
                <div>
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority" required>
                        <option value="low"    @selected(old('priority')==='low')>Low</option>
                        <option value="medium" @selected(old('priority','medium')==='medium')>Medium</option>
                        <option value="high"   @selected(old('priority')==='high')>High</option>
                    </select>
                </div>
                <div>
                    <label for="category">Where did you find this issue?</label>
                    <input id="category" type="text" name="category"
                           placeholder="e.g., Borrow page, Inventory, History"
                           value="{{ old('category') }}">
                    <small class="helper">Examples: Borrow page, Inventory table, History search…</small>
                </div>
            </div>

            <label for="message">Description</label>
            <textarea id="message" name="message" placeholder="Describe what happened, expected behavior, and steps to reproduce."
                      required>{{ old('message') }}</textarea>

            <label for="attachments">Attachments</label>
            <input id="attachments" type="file" name="attachments[]" multiple>
            <small class="helper">Optional: screenshots, PDFs, etc. (max 5MB each)</small>

            <div class="actions">
                <button type="submit" class="btn btn-primary">Submit Report</button>
                <a class="btn-link" href="{{ url()->previous() }}">Back</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>