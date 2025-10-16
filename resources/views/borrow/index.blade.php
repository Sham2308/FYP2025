    <!DOCTYPE html>
    <html>
    <head>
        <title>Borrow Item</title>
        <link rel="icon" type="image/png" href="{{ asset('images/main-logo.png') }}">
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
        </style>
    </head>
    <body>
    <header>
        <div class="logo">TapNBorrow</div>
        <nav>
            <a href="/">Home</a>
            <a href="/borrow">Borrow</a>
            <a href="{{ route('reports.create') }}">Report</a>
        </nav>
    </header>

    <h2></h2>

    <div class="wrap">
        <div class="split">
            <div class="card left">
                <h3>Inventory (Reference)</h3>

                <!-- 🔹 Filter bar -->
            <div style="margin-bottom:10px; display:flex; gap:8px; align-items:center;">
                <input type="text" id="filterInput" placeholder="Search by name, asset ID, or Item ID"
                    style="flex:1; padding:6px 10px; border:1px solid #ccc; border-radius:6px; font-size:13px;">
                <select id="statusFilter" style="padding:6px 8px; border:1px solid #ccc; border-radius:6px; font-size:13px;">
                    <option value="">All Status</option>
                    <option value="available">Available</option>
                    <option value="under repair">Under Repair</option>
                    <option value="borrowed">Borrowed</option>
                    <option value="retire">Retire</option>
                    <option value="stolen">Stolen</option>
                    <option value="missing/lost">Missing/Lost</option>
                </select>
            </div>
                <table>
                    <thead><tr><th>Item ID</th><th>Asset ID</th><th>Name</th><th>Status</th><th>Purchase Date</th></tr></thead>
                    <tbody>
                        @forelse($items as $item)
                        <tr>
                            <td>{{ $item->ItemID }}</td>
                            <td>{{ $item->asset_id }}</td>
                            <td>{{ $item->name }}</td>
                            <td style="
                                font-weight:600;
                                text-transform:capitalize;
                                text-align:center;
                                color:
                                    {{ strtolower($item->status) === 'available' ? '#166534' :
                                        (strtolower($item->status) === 'borrowed' ? '#991b1b' :
                                        (strtolower($item->status) === 'under repair' ? '#92400e' :
                                        (strtolower($item->status) === 'retire' ? '#1e3a8a' :
                                        (strtolower($item->status) === 'stolen' ? '#7f1d1d' :
                                        (strtolower($item->status) === 'missing/lost' ? '#78350f' : '#000'))))) 
                                    }};
                                background:
                                {{ strtolower($item->status) === 'available' ? '#dcfce7' :
                                        (strtolower($item->status) === 'borrowed' ? '#fee2e2' :
                                        (strtolower($item->status) === 'under repair' ? '#fef9c3' :
                                        (strtolower($item->status) === 'retire' ? '#e0e7ff' :
                                        (strtolower($item->status) === 'stolen' ? '#ffe4e6' :
                                        (strtolower($item->status) === 'missing/lost' ? '#fef3c7' : '#fff'))))) 
                                }};
                                border-radius:6px;
                                padding:8px;
                                width:120px;
                            ">
                                {{ $item->status ?? '-' }}
                            </td>
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
                <div class="text-center mt-3">
                    <a href="{{ route('register-user') }}" class="btn btn-primary">Register</a>
                </div>
                <h4></h4>
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
                                <input class="item_uid" name="items[0][item_id]" type="text" placeholder="Item ID" readonly required>
                                <button type="button" class="btn btn-success scanStickerBtn">Scan Sticker</button>
                            </div>
                            <input class="asset_id" name="items[0][asset_id]" type="text" placeholder="Asset ID" readonly>
                            <input class="name" name="items[0][name]" type="text" placeholder="Item Name" readonly>
                        </div>
                    </div>

                    <button type="button" class="btn btn-info" id="addItemBtn" style="margin-top:6px;">+ Add Another Item</button>
                    <h3></h3>
                    <div class="row">
                        <input name="email" type="email" placeholder="Enter Borrower Email" required>
                    </div>

                    <div class="row" style="margin-top:10px;">
                        <input name="borrow_date" type="date" placeholder="Borrow Date">
                        <input name="due_date" type="date" placeholder="Return Date">
                    </div>

                    <div class="actions">
                        <button type="submit" class="btn btn-success">Save Borrow</button>
                    </div>
                </form>

                <h3 style="margin:16px 0 8px;">Recent Borrows</h3>
                <table id="recent-table">
                    <thead>
                        <tr>
                            <th>Card ID</th>
                            <th>Borrower Name</th>
                            <th>User ID</th>
                            <th>Item ID</th>
                            <th>Borrow Date</th>
                            <th>Return Date</th>
                            <th>Borrowed At</th>
                            <th>Returned At</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($recent ?? []) as $i => $r)
                        <tr data-uid="{{ $r['ItemID'] ?? ''}}">
                            <td>{{ $r['CardID'] ?? '-' }}</td>
                            <td>{{ $r['BorrowerName'] ?? '-' }}</td>
                            <td>{{ $r['UserID'] ?? '-' }}</td>
                            <td>{{ $r['ItemID'] ?? '-' }}</td>
                            <td>{{ $r['BorrowDate'] ?? '-' }}</td>
                            <td>{{ $r['ReturnDate'] ?? '-' }}</td>
                            <td>{{ !empty($r['BorrowedAt']) ? date('g:i A', strtotime($r['BorrowedAt'])) : '-' }}</td>
                            <td>{{ !empty($r['ReturnedAt']) ? date('g:i A', strtotime($r['ReturnedAt'])) : '-' }}</td>
                            <td>{{ $r['Status'] ?? '-' }}</td>
                            <td style="min-width:150px;">
                                {{-- ✅ Return button --}}
                                @if(($r['Status'] ?? '') !== 'available' && !empty($r['ItemID']))
                                    <form action="{{ route('borrow.publicReturn', ['uid' => $r['ItemID']]) }}" method="POST" style="display:inline-block;">
                                        @csrf
                                        <button class="btn btn-success btn-sm">Returned</button>
                                    </form>
                                @else
                                    <button class="btn btn-success btn-sm" disabled>Returned</button>
                                @endif

                                {{-- ✅ Delete specific row (by index in Google Sheet) --}}
                                <form action="{{ route('borrow.delete', ['rowIndex' => $r['RowNumber']]) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm delete-btn">Delete</button>
                                </form>
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
    <div class="wrap">
        <div class="card" style="max-width:900px;margin:auto;">
            <h3 style="margin-bottom:16px;font-size:22px;font-weight:700;">🔄 Return by Card Scan</h3>

            <form id="returnForm" method="POST" action="{{ route('return.confirm') }}">
                @csrf

                <!-- Scan Card Row -->
                <div class="row" style="align-items:center;gap:10px;margin-bottom:18px;">
                    <div style="flex:1;display:flex;align-items:center;gap:10px;">
                        <input type="text" id="return_card_uid" name="card_uid"
                            placeholder="Tap your card to scan..."
                            readonly
                            style="background:#f3f4f6;color:#374151;font-weight:500;">
                        <button type="button" id="returnScanBtn" class="btn btn-warning" style="white-space:nowrap;">
                            <span style="font-size:13px;font-weight:700;">Scan Card</span>
                        </button>
                    </div>
                </div>

                <!-- Borrowed Items Section -->
                <div id="borrowedItemsSection" class="hidden" style="border-top:1px solid #e5e7eb;padding-top:12px;">
                    <h4 style="margin-bottom:10px;font-size:18px;font-weight:600;color:#111;">📦 Borrowed Items</h4>
                    <div id="borrowedItemsList"
                        style="display:flex;flex-direction:column;gap:10px;margin-bottom:18px;">
                        <!-- Items will be listed here dynamically -->
                    </div>

                    <div class="actions" style="justify-content:flex-end;">
                        <button type="submit"
                            id="returnAllBtn"
                            class="btn btn-success"
                            style="padding:10px 18px;font-size:14px;">
                            ✅ Return All Items
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
    async function fetchUser(cardUid){
        try{
            const res = await fetch(`/api/borrow/user/${cardUid}`);
            if(!res.ok) throw new Error("User not found in Google Sheet");
            const data = await res.json();
            document.getElementById('card_uid').value = data.card_id || '';
            document.getElementById('user_id').value = data.student_id || '';
            document.getElementById('borrower_name').value = data.name || '';
        }catch(err){ alert(err.message); }
    }

    let itemIndex = 0;

    document.getElementById('addItemBtn').addEventListener('click', () => {
        itemIndex++; // increment first — now row 1 added = has clear button

        const container = document.getElementById('items-container');

        // ✅ Only added rows (not the original static one) get the Clear button
        const extraButton = `<button type="button" class="btn btn-danger removeItemBtn">Clear</button>`;

        const div = document.createElement('div');
        div.className = 'row item-row';
        div.innerHTML = `
            <div style="display:flex;gap:6px;flex:1;">
                <input class="item_uid" name="items[${itemIndex}][item_id]" type="text" placeholder="Item ID" readonly required>
                <button type="button" class="btn btn-success scanStickerBtn">Scan Sticker</button>
                ${extraButton}
            </div>
            <input class="asset_id" name="items[${itemIndex}][asset_id]" type="text" placeholder="Asset ID" readonly>
            <input class="name" name="items[${itemIndex}][name]" type="text" placeholder="Item Name" readonly>
        `;
        container.appendChild(div);
    });

    // ✅ Allow removing the extra rows
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('removeItemBtn')) {
            const row = e.target.closest('.item-row');
            if (row) row.remove();
        }
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

    document.addEventListener('DOMContentLoaded', () => {
    const borrowForm = document.querySelector('form[action*="/borrow/publicStore"]') || document.querySelector('form');
    if (!borrowForm) {
        console.error("❌ Borrow form not found!");
        return;
    }

    console.log("✅ Borrow form detected");

    borrowForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        console.log("📤 Submitting to:", borrowForm.action);

        const formData = new FormData(borrowForm);

        try {
        const res = await fetch(borrowForm.action, {
            method: 'POST',
            body: formData,
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        });

        console.log("✅ Response:", res.status);
        if (!res.ok) throw new Error("Server returned " + res.status);

        alert("✅ Borrow recorded successfully!");
        borrowForm.reset();

        } catch (err) {
        console.error("❌ Borrow submit failed:", err);
        alert("❌ " + err.message);
        }
    });
    });

    document.getElementById('returnScanBtn').addEventListener('click', async () => {
        const cardUid = document.getElementById('return_card_uid');
        const btn = document.getElementById('returnScanBtn');
        btn.disabled = true; btn.textContent = "Scanning...";
        try {
            await fetch('/api/request-scan',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({type:'card'})});
            alert("Please tap your user card...");
            let uid=null;
            for(let i=0;i<20;i++){
                await new Promise(r=>setTimeout(r,1000));
                const r=await fetch('/api/read-uid'); const d=await r.json();
                if(d.uid){uid=d.uid;break;}
            }
            if(!uid) throw new Error("No card detected");
            cardUid.value=uid;
            btn.textContent = "Scan Card"; btn.disabled = false;

            const res=await fetch(`/return/fetch/${uid}`);
            const data=await res.json();
            const section=document.getElementById('borrowedItemsSection');
            const list=document.getElementById('borrowedItemsList');
            list.innerHTML='';

            if(!data.success){alert(data.error||'No borrowed items found');section.classList.add('hidden');return;}

            // build nice item cards
            data.items.forEach(item=>{
                const div=document.createElement('div');
                div.style.cssText="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;";
                div.innerHTML=`
                    <label style="display:flex;align-items:center;gap:10px;">
                        <input type="checkbox" name="item_ids[]" value="${item.ItemID}" checked style="transform:scale(1.2);cursor:pointer;">
                        <div>
                            <div style="font-weight:600;color:#111;">${item.ItemID}</div>
                            <div style="font-size:13px;color:#4b5563;">${item.Name || ''}</div>
                        </div>
                    </label>
                    <span style="font-size:13px;color:#2563eb;font-weight:600;">Borrowed</span>
                `;
                list.appendChild(div);
            });
            section.classList.remove('hidden');
        } catch(err){
            alert("Error scanning card: "+err.message);
            btn.textContent = "Scan Card"; btn.disabled = false;
        }
    });
    // 🔹 Inventory Filter (case-insensitive)
    const filterInput = document.getElementById('filterInput');
    const statusFilter = document.getElementById('statusFilter');
    const table = document.querySelector('.left table');
    const rows = table.querySelectorAll('tbody tr');

    function filterTable() {
        const text = filterInput.value.trim().toLowerCase();
        const status = statusFilter.value.trim().toLowerCase();
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            const uid = cells[0]?.innerText.trim().toLowerCase() || '';
            const asset = cells[1]?.innerText.trim().toLowerCase() || '';
            const name = cells[2]?.innerText.trim().toLowerCase() || '';
            const stat = cells[3]?.innerText.trim().toLowerCase() || '';
            const matchText = !text || uid.includes(text) || asset.includes(text) || name.includes(text);
            const matchStatus = !status || stat === status;
            row.style.display = (matchText && matchStatus) ? '' : 'none';
        });
    }
    filterInput.addEventListener('input', filterTable);
    statusFilter.addEventListener('change', filterTable);
    </script>
    </body>
    </html>
