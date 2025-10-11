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

    /* ✅ Expand the layout */
    .wrap { width:98%; max-width:1800px; margin:0 auto 40px; }
    .card {
      border:2px solid #999; border-radius:12px; padding:20px; background:#f9fafb; margin-top:14px;
      overflow-x:auto; white-space:nowrap;
      box-shadow:0 2px 6px rgba(0,0,0,0.1);
    }

    /* styled horizontal scrollbar */
    .card::-webkit-scrollbar { height:10px; }
    .card::-webkit-scrollbar-track { background:#e5e7eb; border-radius:10px; }
    .card::-webkit-scrollbar-thumb { background:#9ca3af; border-radius:10px; }
    .card::-webkit-scrollbar-thumb:hover { background:#6b7280; }

    /* ✅ Larger table border */
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
$safeDate=function($v,$fmt='d-m-Y H:i'){
 try{
  if(!$v)return'-';
  if($v instanceof \DateTimeInterface)
    return Carbon::instance($v)->tz(config('app.timezone','Asia/Brunei'))->format($fmt);
  if(is_numeric($v)){
    $n=(float)$v;
    if($n>1e9&&$n<4102444800)return Carbon::createFromTimestampUTC((int)$n)->tz(config('app.timezone','Asia/Brunei'))->format($fmt);
    if($n>25569&&$n<600000)return Carbon::createFromTimestampUTC((int)(($n-25569)*86400))->tz(config('app.timezone','Asia/Brunei'))->format($fmt);
  }
  $s=trim((string)$v);
  if(preg_match('/\d/',$s)){ $c=Carbon::parse($s); if($c)return$c->tz(config('app.timezone','Asia/Brunei'))->format($fmt); }
  return'-';
 }catch(\Throwable $e){return'-';}
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
<th>Timestamp</th><th>BorrowID</th><th>UserID</th><th>Borrower Name</th><th>UID</th><th>AssetID</th><th>Name</th>
<th>Borrow Date</th><th>Return Date</th><th>Borrowed At</th><th>Returned At</th><th>Status</th><th>Remarks</th>
<th class="sortable">Days Borrowed ⬍</th>
</tr>
</thead>
<tbody>
@foreach($history as $row)
<tr>
<td>{{ $safeDate($row['Timestamp']??null,'d-m-Y H:i') }}</td>
<td>{{ $row['BorrowID']??'-' }}</td>
<td>{{ $row['UserID']??'-' }}</td>
<td>{{ $row['BorrowerName']??'-' }}</td>
<td>{{ $row['UID']??'-' }}</td>
<td>{{ $row['AssetID']??'-' }}</td>
<td>{{ $row['Name']??'-' }}</td>
<td>{{ $safeDate($row['BorrowDate']??null,'d-m-Y') }}</td>
<td>{{ $safeDate($row['ReturnDate']??null,'d-m-Y') }}</td>
<td>{{ $safeDate($row['BorrowedAt']??null,'d-m-Y') }}</td>
<td>{{ $safeDate($row['ReturnedAt']??null,'d-m-Y') }}</td>
<td>{{ $row['Status']??'-' }}</td>
<td>{{ $row['Remarks']??'-' }}</td>
<td><span class="days-badge" data-borrowed-at="{{ $row['BorrowedAt']??'' }}" data-returned-at="{{ $row['ReturnedAt']??'' }}"></span></td>
</tr>
@endforeach
</tbody>
</table>
@endif
</div>
</div>

<script>
function updateDaysLeft(){
 const badges=document.querySelectorAll('.days-badge'),today=new Date();
 badges.forEach(el=>{
  const b=el.dataset.borrowedAt,r=el.dataset.returnedAt;
  if(!b||b==='-'){el.textContent='';return;}
  const bd=new Date(b),ed=r&&r!=='-'?new Date(r):today;
  if(isNaN(bd)||isNaN(ed)){el.textContent='';return;}
  const diff=Math.ceil((ed-bd)/(1000*60*60*24));
  el.textContent=`${diff} day${diff>1?'s':''}`;
  el.className='days-badge '+(r&&r!=='-'?'completed':'ongoing');
  el.dataset.days=diff;
 });
}
updateDaysLeft(); setInterval(updateDaysLeft,60000);
document.addEventListener('DOMContentLoaded',()=>{
 const table=document.getElementById('historyTable'),head=table.querySelector('th.sortable'); let asc=true;
 head.addEventListener('click',()=>{
  const rows=[...table.querySelectorAll('tbody tr')];
  rows.sort((a,b)=>{
   const da=parseInt(a.querySelector('.days-badge')?.dataset.days||0),
         db=parseInt(b.querySelector('.days-badge')?.dataset.days||0);
   return asc?da-db:db-da;
  });
  asc=!asc; rows.forEach(r=>table.querySelector('tbody').appendChild(r));
 });
});
</script>
</body>
</html>
