<!DOCTYPE html>
<html>
<head>
    <title>Borrow Item</title>
    <link rel="icon" type="image/png" href="{{ asset('pblogo (2).png') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body{margin:0;font-family:system-ui,Arial,sans-serif;background:#fff;color:#111;font-size:14px;}
        header{display:flex;justify-content:space-between;align-items:center;padding:14px 30px;background:#2563eb;color:#fff;}
        header .logo{font-size:20px;font-weight:700;}
        header nav a{color:#fff;text-decoration:none;margin-left:20px;font-weight:600;}
        header nav a:hover{text-decoration:underline;}
        h2{text-align:left;margin:24px 0 8px;font-size:28px;padding-left:24px;font-weight:700;}
        .wrap{width:98%;max-width:1600px;margin:0 auto 40px;}
        .card{border:1px solid #e5e7eb;border-radius:12px;background:#fafafa;padding:18px;margin:0 0 18px 0;}
        .row{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:8px;}
        .row input,.row textarea{padding:10px;border:1px solid #cbd5e1;border-radius:8px;min-width:160px;flex:1;font-size:14px;}
        input[readonly]{background:#e5e7eb;color:#374151;}
        textarea{min-height:60px;resize:vertical;width:100%;}
        .btn{padding:10px 14px;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:14px;}
        .btn-success{background:#16a34a;color:#fff;}
        .btn-danger{background:#dc2626;color:#fff;}
        .btn-warning{background:#f59e0b;color:#fff;}
        .btn-info{background:#0ea5e9;color:#fff;}
        .btn-sm{padding:6px 10px;font-size:12px;}
        table{border-collapse:collapse;width:100%;margin-top:10px;font-size:13px;}
        th,td{border:1px solid #d1d5db;padding:8px;text-align:center;vertical-align:middle;}
        th{background:#f5f5f5;}
        .actions{display:flex;gap:10px;margin-top:10px;}
        .split{display:flex;gap:24px;align-items:flex-start;}
        .left{flex:0.35}
        .right{flex:0.65}
        .alert{padding:10px;border-radius:8px;margin-bottom:10px}
        .alert-success{background:#dcfce7;color:#166534;}
        .alert-danger{background:#fee2e2;color:#991b1b;}
    </style>
</head>
<body>
<header>
    <div class="logo">TapNBorrow</div>
    <nav>
        <a href="/">Home</a>
        <a href="/borrow">Borrow</a>
        <a href="{{ route('history.index') }}">History</a>
    </nav>
</header>

<h2>Borrow System</h2>

<div class="wrap">
    <div class="split">
        <div class="card left">
            <h3>Inventory (Reference)</h3>
            <table>
                <thead><tr><th>UID</th><th>Asset ID</th><th>Name</th><th>Status</th><th>Purchase Date</th></tr></thead>
                <tbody>
                    @forelse($items as $item)
                    <tr>
                        <td>{{ $item->uid }}</td>
                        <td>{{ $item->asset_id }}</td>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->status }}</td>
                        <td>{{ $item->purchase_date }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5">No items yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card right">
            <h3>Borrow Details</h3>

            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

            <form action="{{ route('borrow.publicStore') }}" method="POST">
                @csrf
                <div class="row">
                    <input id="card_uid" name="card_uid" type="text" placeholder="Card UID" readonly>
                    <button type="button" id="scanBtn" class="btn btn-warning">Scan Card</button>
                    <input id="user_id" name="user_id" type="text" placeholder="Student / Staff ID" readonly required>
                    <input id="borrower_name" name="borrower_name" type="text" placeholder="Borrower Name" readonly required>
                </div>

                <div id="items-container">
                    <div class="row item-row">
                        <div style="display:flex;gap:6px;flex:1;">
                            <input class="item_uid" name="items[0][uid]" type="text" placeholder="Item UID" readonly required>
                            <button type="button" class="btn btn-success scanStickerBtn">Scan Sticker</button>
                        </div>
                        <input class="asset_id" name="items[0][asset_id]" type="text" placeholder="Asset ID" readonly>
                        <input class="name" name="items[0][name]" type="text" placeholder="Item Name" readonly>
                    </div>
                </div>

                <button type="button" class="btn btn-info" id="addItemBtn" style="margin-top:6px;">+ Add Another Item</button>

                <div class="row" style="margin-top:10px;">
                    <input name="borrow_date" type="date" placeholder="Borrow Date">
                    <input name="due_date" type="date" placeholder="Return Date">
                </div>

                <div class="row" style="margin-top:8px;">
                    <textarea name="remarks" placeholder="Remarks / Notes"></textarea>
                </div>

                <div class="actions">
                    <button type="submit" class="btn btn-success">Save Borrow</button>
                    <button type="reset" class="btn btn-danger">Clear</button>
                </div>
            </form>

            <h3 style="margin:16px 0 8px;">Recent Borrows</h3>
            <table id="recent-table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Borrower Name</th>
                        <th>UID</th>
                        <th>Asset</th>
                        <th>Borrow Date</th>
                        <th>Return Date</th>
                        <th>Borrowed At</th>
                        <th>Returned At</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($recent ?? []) as $r)
                    <tr data-uid="{{ $r['UID'] }}">
                        <td>{{ $r['UserID'] }}</td>
                        <td>{{ $r['BorrowerName'] }}</td>
                        <td>{{ $r['UID'] }}</td>
                        <td>{{ $r['AssetID'] }}</td>
                        <td>{{ $r['BorrowDate'] }}</td>
                        <td>{{ $r['ReturnDate'] }}</td>
                        <td>{{ $r['BorrowedAt'] }}</td>
                        <td>{{ $r['ReturnedAt'] ?: '-' }}</td>
                        <td>{{ $r['Status'] }}</td>
                        <td style="min-width:150px;">
                            @if($r['Status'] !== 'available')
                                <form action="{{ route('borrow.publicReturn', ['uid' => $r['UID']]) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    <button class="btn btn-success btn-sm">Returned</button>
                                </form>
                            @else
                                <button class="btn btn-success btn-sm" disabled>Returned</button>
                            @endif
                            <button type="button" class="btn btn-danger btn-sm delete-btn" data-uid="{{ $r['UID'] }}">Delete</button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10">No borrows yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function fetchUser(cardUid){
    try{
        const res = await fetch(`/api/borrow/user/${cardUid}`);
        if(!res.ok) throw new Error("User not found in Google Sheet");
        const data = await res.json();
        document.getElementById('card_uid').value = data.uid || '';
        document.getElementById('user_id').value = data.student_id || '';
        document.getElementById('borrower_name').value = data.name || '';
    }catch(err){ alert(err.message); }
}

let itemIndex = 1;
document.getElementById('addItemBtn').addEventListener('click',()=>{
    const container=document.getElementById('items-container');
    const div=document.createElement('div'); div.className='row item-row';
    div.innerHTML = `
        <div style="display:flex;gap:6px;flex:1;">
            <input class="item_uid" name="items[${itemIndex}][uid]" type="text" placeholder="Item UID" readonly required>
            <button type="button" class="btn btn-success scanStickerBtn">Scan Sticker</button>
        </div>
        <input class="asset_id" name="items[${itemIndex}][asset_id]" type="text" placeholder="Asset ID" readonly>
        <input class="name" name="items[${itemIndex}][name]" type="text" placeholder="Item Name" readonly>
    `;
    container.appendChild(div);
    itemIndex++;
});

document.addEventListener('click',async e=>{
    if(e.target.classList.contains('scanStickerBtn')){
        const row=e.target.closest('.item-row');
        const uidInput=row.querySelector('.item_uid');
        const assetInput=row.querySelector('.asset_id');
        const nameInput=row.querySelector('.name');
        try{
            await fetch('/api/request-scan',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({type:'sticker'})});
            alert("Please tap the item’s NFC sticker...");
            let uid=null;
            for(let i=0;i<20;i++){
                await new Promise(r=>setTimeout(r,1000));
                const r=await fetch('/api/read-uid'); const d=await r.json();
                if(d.uid){uid=d.uid;break;}
            }
            if(!uid) throw new Error("No sticker detected");
            uidInput.value=uid;
            const itemRes=await fetch(`/borrow/fetch/${uid}`);
            if(!itemRes.ok) throw new Error("Item not found in Google Sheet");
            const item=await itemRes.json();
            assetInput.value=item.asset_id||''; nameInput.value=item.name||'';
        }catch(err){ alert("Error scanning sticker: "+err.message); }
    }
});

document.getElementById('scanBtn').addEventListener('click',async()=>{
    try{
        await fetch('/api/request-scan',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({type:'card'})});
        alert("Please tap your user card...");
        let uid=null;
        for(let i=0;i<20;i++){
            await new Promise(r=>setTimeout(r,1000));
            const r=await fetch('/api/read-uid'); const d=await r.json();
            if(d.uid){uid=d.uid;break;}
        }
        if(!uid) throw new Error("No card detected");
        document.getElementById('card_uid').value=uid;
        await fetchUser(uid);
    }catch(err){ alert("Error scanning card: "+err.message); }
});

// Delete recent borrow (visual only)
document.querySelectorAll('.delete-btn').forEach(btn=>{
    btn.addEventListener('click',()=>{
        const uid=btn.dataset.uid;
        if(!confirm(`Remove record ${uid}?`)) return;
        const row=btn.closest('tr');
        if(row) row.remove();
        document.querySelectorAll('.left table tbody tr').forEach(r=>{
            if(r.cells[0].textContent.trim()===uid){
                r.cells[3].textContent='available';
            }
        });
    });
});

// Update left table on borrow save
const borrowForm=document.querySelector('form[action*="borrow.publicStore"]');
if(borrowForm){
    borrowForm.addEventListener('submit',()=>{
        setTimeout(()=>{
            document.querySelectorAll('.item_uid').forEach(i=>{
                const uid=i.value.trim();
                document.querySelectorAll('.left table tbody tr').forEach(r=>{
                    if(r.cells[0].textContent.trim()===uid){
                        r.cells[3].textContent='borrowed';
                    }
                });
            });
        },1000);
    });
}
</script>
</body>
</html>
