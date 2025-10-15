<!DOCTYPE html>
<html>
<head>
  <title>TapNBorrow</title>
  <link rel="icon" type="image/png" href="{{ asset('images/main-logo.png') }}">
  <style>
    body { margin:0; font-family:system-ui,Arial,sans-serif; background:#fff; color:#111; }
    header {
      display:flex; justify-content:space-between; align-items:center;
      padding:14px 30px; background:#2563eb; color:#fff;
    }
    .brand { display:flex; align-items:center; gap:10px; text-decoration:none; color:#fff; }
    .brand img { height:26px; width:auto; display:block; }
    header .logo { font-size:20px; font-weight:700; letter-spacing:.5px; }

    header nav a { color:#fff; text-decoration:none; margin-left:20px; font-weight:600; }
    header nav a:hover { text-decoration:underline; }

    h2 { text-align:center; margin:24px 0 8px; }

    .wrap { width:98%; max-width:1800px; margin:0 auto 40px; }
    .card {
      border:2px solid #999; border-radius:12px; padding:20px; background:#f9fafb; margin-top:14px;
      overflow-x:auto; white-space:nowrap;
      box-shadow:0 2px 6px rgba(0,0,0,0.1);
    }
    .card::-webkit-scrollbar { height:10px; }
    .card::-webkit-scrollbar-track { background:#e5e7eb; border-radius:10px; }
    .card::-webkit-scrollbar-thumb { background:#9ca3af; border-radius:10px; }
    .card::-webkit-scrollbar-thumb:hover { background:#6b7280; }

    table { border-collapse:collapse; width:100%; margin-top:16px; font-size:14px; min-width:1300px; border:2px solid #999; }
    th,td {
      border:2px solid #999;
      padding:10px;
      text-align:center;
      vertical-align:middle;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }
    th { background:#f3f4f6; font-weight:700; color:#111; }
    th.sortable { cursor:pointer; }

    .btn-import {
      background:#16a34a; color:#fff; padding:8px 14px; border:none; border-radius:6px;
      cursor:pointer; font-weight:600; margin-bottom:12px;
    }
    .btn-import:hover { background:#15803d; }

    .days-badge {
      display:inline-block; padding:4px 10px; border-radius:8px; font-weight:600; font-size:13px; color:#fff;
    }
    .days-badge.ongoing { background:#f59e0b; }
    .days-badge.completed { background:#16a34a; }
  </style>
</head>
<body>
@php
use Carbon\Carbon;

$tz = config('app.timezone', 'Asia/Brunei');

$safeDate = function($v, $fmt = 'd-m-Y') use ($tz) {
  try {
    if ($v === null || $v === '') return '-';
    if ($v instanceof \DateTimeInterface) {
      return Carbon::instance($v)->tz($tz)->format($fmt);
    }
    if (is_numeric($v)) {
      $n = (float) $v;
      // Excel serial date (>= 25569 means 1970-01-01)
      if ($n > 25569 && $n < 600000) {
        $ts = ($n - 25569) * 86400;
        return Carbon::createFromTimestampUTC((int) round($ts))->tz($tz)->format($fmt);
      }
      // Unix seconds
      if ($n > 1e9 && $n < 4102444800) {
        return Carbon::createFromTimestampUTC((int) $n)->tz($tz)->format($fmt);
      }
    }
    $s = trim((string) $v);
    if (preg_match('/\d/', $s)) {
      return Carbon::parse($s)->tz($tz)->format($fmt);
    }
    return '-';
  } catch (\Throwable $e) {
    return '-';
  }
};

$safeTime = function($v, $fmt = 'H:i') use ($tz) {
  try {
    if ($v === null || $v === '') return '-';
    if ($v instanceof \DateTimeInterface) {
      return Carbon::instance($v)->tz($tz)->format($fmt);
    }
    if (is_numeric($v)) {
      $n = (float) $v;
      // Excel time fraction (0..1)
      if ($n >= 0 && $n < 1) {
        $seconds = (int) round($n * 86400);
        return gmdate($fmt, $seconds);
      }
      // Unix seconds
      if ($n > 1e9 && $n < 4102444800) {
        return Carbon::createFromTimestampUTC((int) $n)->tz($tz)->format($fmt);
      }
    }
    $s = trim((string) $v);
    // Plain "HH:MM" or "HH:MM:SS"
    if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $s)) {
      return substr($s, 0, 5);
    }
    // Datetime string → show only time
    return Carbon::parse($s)->tz($tz)->format($fmt);
  } catch (\Throwable $e) {
    return '-';
  }
};

$isoDate = function($v) {
  try {
    if ($v === null || $v === '') return '';
    if (is_numeric($v)) {
      $n = (float) $v;
      if ($n > 25569 && $n < 600000) {
        $ts = ($n - 25569) * 86400;
        return gmdate('Y-m-d', (int) round($ts));
      }
      if ($n > 1e9 && $n < 4102444800) {
        return gmdate('Y-m-d', (int) $n);
      }
    }
    return Carbon::parse($v)->format('Y-m-d');
  } catch (\Throwable $e) {
    return '';
  }
};
@endphp

<header>
  <a href="/" class="brand"><img src="{{ asset('images/icon-logo.png') }}"><div class="logo">TapNBorrow</div></a>
  <nav><a href="/">Home</a><a href="/borrow">Borrow</a><a href="{{ route('nfc.inventory') }}">Inventory</a><a href="{{ route('history.index') }}">History</a></nav>
</header>

<h2>Borrow History</h2>
<div class="wrap">
<div class="card">
<form action="{{ route('history.import.google') }}" method="POST">@csrf<button type="submit" class="btn-import">Import from Google Sheets</button></form>

<form method="GET" action="{{ route('history.index') }}" style="margin-bottom:12px;">
  <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
    <input type="text" name="user" placeholder="Search UserID or Name" value="{{ request('user') }}" style="padding:6px 10px;border:1px solid #ccc;border-radius:6px;flex:1;min-width:180px;">
    <select name="status" style="padding:6px 10px;border:1px solid #ccc;border-radius:6px;">
      <option value="">All Status</option>
      <option value="borrowed" {{ request('status')=='borrowed'?'selected':'' }}>Borrowed</option>
      <option value="available" {{ request('status')=='available'?'selected':'' }}>Available</option>
    </select>
    <input type="date" name="from" value="{{ request('from') }}" style="padding:6px 10px;border:1px solid #ccc;border-radius:6px;">
    <span>to</span>
    <input type="date" name="to" value="{{ request('to') }}" style="padding:6px 10px;border:1px solid #ccc;border-radius:6px;">
    <button type="submit" style="background:#2563eb;color:#fff;border:none;padding:6px 14px;border-radius:6px;font-weight:600;cursor:pointer;">Filter</button>
    <a href="{{ route('history.index') }}" style="padding:6px 14px;border:1px solid #ccc;border-radius:6px;text-decoration:none;color:#111;">Reset</a>
  </div>
</form>

@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if(!empty($error))<div class="alert alert-danger">{{ $error }}</div>@endif

@if(!empty($history))
<table id="historyTable">
  <thead>
    <tr>
      <th>UID</th>
      <th>BorrowID</th>
      <th>UserID</th>
      <th>BorrowerName</th>
      <th>AssetID</th>
      <th>Name</th>
      <th>BorrowDate</th>
      <th>ReturnDate</th>
      <th>BorrowedAt</th>
      <th>ReturnedAt</th>
      <th>Status</th>
      <th>Remarks</th>
      <th class="sortable">Days Borrowed ⬍</th>
    </tr>
  </thead>
  <tbody>
  @foreach($history as $row)
    @php
      $borrowDateIso = $isoDate($row['BorrowDate'] ?? null);
      $returnDateIso = $isoDate($row['ReturnDate'] ?? null);
    @endphp
    <tr>
      <td>{{ $row['UID'] ?? '-' }}</td>
      <td>{{ $row['BorrowID'] ?? '-' }}</td>
      <td>{{ $row['UserID'] ?? '-' }}</td>
      <td>{{ $row['BorrowerName'] ?? '-' }}</td>
      <td>{{ $row['AssetID'] ?? '-' }}</td>
      <td>{{ $row['Name'] ?? '-' }}</td>
      {{-- match sheet formats exactly --}}
      <td>{{ $safeDate($row['BorrowDate'] ?? null, 'd-m-Y') }}</td>
      <td>{{ $safeDate($row['ReturnDate'] ?? null, 'd-m-Y') }}</td>
      <td>{{ $safeTime($row['BorrowedAt'] ?? null, 'H:i') }}</td>
      <td>{{ $safeTime($row['ReturnedAt'] ?? null, 'H:i') }}</td>
      <td>{{ $row['Status'] ?? '-' }}</td>
      <td>{{ $row['Remarks'] ?? '-' }}</td>
      <td>
        <span class="days-badge"
              data-start="{{ $borrowDateIso }}"
              data-end="{{ $returnDateIso }}"></span>
      </td>
    </tr>
  @endforeach
  </tbody>
</table>
@endif
</div>
</div>

<script>
function updateDays(){
  const badges = document.querySelectorAll('.days-badge');
  const today = new Date();
  badges.forEach(el => {
    const start = el.dataset.start;
    const end   = el.dataset.end;
    if (!start) { el.textContent = ''; el.dataset.days = 0; return; }

    // Use midnight UTC to avoid TZ shifts
    const sd = new Date(start + 'T00:00:00Z');
    const todayUTC = new Date(Date.UTC(today.getUTCFullYear(), today.getUTCMonth(), today.getUTCDate()));
    const edRaw = end ? new Date(end + 'T00:00:00Z') : todayUTC;
    const ed = isNaN(edRaw.getTime()) ? todayUTC : edRaw;

    if (isNaN(sd) || isNaN(ed)) { el.textContent = ''; el.dataset.days = 0; return; }

    let diff = Math.ceil((ed - sd) / 86400000); // ms → days
    if (diff < 1) diff = 1; // minimum 1 day
    el.textContent = `${diff} day${diff > 1 ? 's' : ''}`;
    el.className = 'days-badge ' + (end ? 'completed' : 'ongoing');
    el.dataset.days = diff;
  });
}

updateDays();
setInterval(updateDays, 60000);

document.addEventListener('DOMContentLoaded', () => {
  const table = document.getElementById('historyTable');
  const head  = table?.querySelector('th.sortable');
  if (!head) return;
  let asc = true;
  head.addEventListener('click', () => {
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    rows.sort((a, b) => {
      const da = parseInt(a.querySelector('.days-badge')?.dataset.days || '0', 10);
      const db = parseInt(b.querySelector('.days-badge')?.dataset.days || '0', 10);
      return asc ? da - db : db - da;
    });
    asc = !asc;
    const tbody = table.querySelector('tbody');
    rows.forEach(r => tbody.appendChild(r));
  });
});
</script>
</body>
</html>
