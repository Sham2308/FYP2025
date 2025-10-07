<!DOCTYPE html>
<html>
<head>
    <title>TapNBorrow</title>
    <link rel="icon" type="image/png" href="{{ asset('images/main-logo.png') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { margin:0; font-family: system-ui, Arial, sans-serif; background:#ffffff; color:#111; font-size:14px; }
        header { display:flex; justify-content:space-between; align-items:center;
                 padding:14px 30px; background:#2563eb; color:#fff; }
        header .logo {
            display:flex;
            align-items:center;
            gap:8px;
            font-size:20px;
            font-weight:700;
            letter-spacing:0.5px;
        }
        header .logo img { height:24px; width:auto; }
        header nav a { color:#fff; text-decoration:none; margin-left:20px; font-weight:600; }
        header nav a:hover { text-decoration:underline; }
        h2 { text-align:center; margin:24px 0 8px; font-size:22px; }
        
        /* 🔹 Bigger container */
        .wrap { width:98%; max-width:1600px; margin: 0 auto 40px; display:flex; gap:24px; align-items:flex-start; }

        /* 🔹 Card size + padding */
        .card { border:1px solid #e5e7eb; border-radius:12px; padding:18px; background:#f9fafb; margin-top:14px; flex:1; font-size:14px; }

        /* 🔹 Rows spacing + compact inputs */
        .row { display:flex; gap:12px; flex-wrap:wrap; }
        .row input, .row textarea {
            padding:8px 10px; border:1px solid #cbd5e1; border-radius:8px;
            min-width:150px; flex:1; font-size:14px;
        }
        input[readonly] { background:#e5e7eb; color:#374151; }
        textarea { min-height:60px; resize:vertical; flex:1; }

        .actions { display:flex; gap:10px; margin-top:12px; }
        .btn { padding:8px 14px; border:none; border-radius:8px; font-weight:600; cursor:pointer; font-size:14px; }
        .btn-success { background:#16a34a; color:#fff; }
        .btn-danger { background:#dc2626; color:#fff; }
        .btn-warning { background:#f59e0b; color:#fff; }
        .btn-success:hover { background:#15803d; }
        .btn-danger:hover { background:#b91c1c; }
        .btn-warning:hover { background:#d97706; }
        .btn-sm { padding:5px 10px; font-size:12px; }

        /* 🔹 Table adjustments */
        table { border-collapse: collapse; width: 100%; margin-top:16px; font-size:13px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: center; vertical-align: middle; }
        th { background: #f5f5f5; font-size:13px; }
        td form { display:block; margin:4px 0; }

        /* 🔹 Resize inventory vs borrow form */
        .inventory-card { flex:0.35; }
        .borrow-card { flex:0.65; }

        /* ───────────────── Chat widget (added) ───────────────── */
        .chat-box { position: fixed; bottom:20px; right:20px; width:320px; background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow: 0 10px 30px rgba(0,0,0,.12); overflow:hidden; z-index: 9999; }
        .chat-header { display:flex; align-items:center; justify-content:space-between; padding:8px 12px; background:#2563eb; color:#fff; font-weight:600; }
        .chat-min { background:transparent; border:none; color:#fff; cursor:pointer; padding:4px 8px; }
        .chat-body { display:flex; flex-direction:column; height:360px; }
        .chat-messages { flex:1; overflow-y:auto; padding:10px; background:#fff; }
        .chat-row { margin:6px 0; }
        .chat-meta { color:#6b7280; font-size:12px; margin-bottom:2px; }
        .chat-bubble { display:inline-block; max-width:85%; background:#f3f4f6; border:1px solid #e5e7eb; border-radius:14px; padding:8px 10px; font-size:13px; }
        .chat-form { display:flex; gap:8px; padding:8px; border-top:1px solid #e5e7eb; }
        .chat-input { flex:1; padding:8px 10px; border:1px solid #cbd5e1; border-radius:10px; font-size:14px; }
        .chat-send { background:#2563eb; color:#fff; border:none; padding:8px 12px; border-radius:10px; font-weight:600; cursor:pointer; }
        .chat-send:hover { background:#1d4ed8; }
        .hidden { display:none; }
    </style>
</head>
<body>

    <!-- Header -->
    <header>
        <div class="logo">
            <img src="{{ asset('images/icon-logo.png') }}" alt="TapNBorrow logo">
            <span>TapNBorrow</span>
        </div>

        <nav>
            <a href="/">Home</a>
            <a href="/borrow">Borrow</a>
            <a href="{{ route('reports.create') }}">Report</a>
        </nav>
    </header>

    <h2>Borrow System</h2>

    <div class="wrap">
        <!-- Left: Inventory Table -->
        <div class="card inventory-card" style="flex:0.4;">
            <h3 style="margin:6px 0 12px;">Inventory (Reference)</h3>
            <table>
                <thead>
                    <tr>
                        <th>UID</th>
                        <th>Asset ID</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Purchase Date</th>
                    </tr>
                </thead>
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

        <!-- Right: Borrow Form + Log -->
        <div class="card borrow-card" style="flex:0.6;">
            <h3 style="margin:6px 0 12px;">Borrow Details</h3>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form action="{{ route('borrow.store') }}" method="POST">
                @csrf
                <div class="row">
                    <!-- Card UID (user scan) -->
                    <input id="card_uid" name="card_uid" type="text" placeholder="Card UID" readonly>
                    <button type="button" id="scanBtn" class="btn btn-warning">Scan Card</button>
                    <input id="user_id" name="user_id" type="text" placeholder="Student / Staff ID" readonly required>
                    <input id="borrower_name" name="borrower_name" type="text" placeholder="Borrower Name" readonly required>
                </div>

                <div class="row" style="margin-top:10px;">
                    <!-- Item UID (equipment) -->
                    <div style="display:flex; gap:6px; flex:1;">
                        <input id="item_uid" name="uid" type="text" placeholder="Item UID" required readonly>
                        <button type="button" id="scanStickerBtn" class="btn btn-success">Scan Sticker</button>
                    </div>
                    <input id="asset_id" name="asset_id" type="text" placeholder="Asset ID" readonly>
                    <input id="name" name="name" type="text" placeholder="Item Name" readonly>
                </div>

                <div class="row" style="margin-top:10px;">
                    <input id="status" name="status" type="text" placeholder="Status" readonly>
                    <input id="purchase_date" name="purchase_date" type="text" placeholder="Purchase Date" readonly>
                </div>

                <div class="row" style="margin-top:10px;">
                    <input name="borrow_date" type="date" placeholder="Borrow Date">
                    <input name="due_date" type="date" placeholder="Return Date">
                </div>

                <div class="row" style="margin-top:10px;">
                    <textarea name="remarks" placeholder="Remarks / Notes"></textarea>
                </div>

                <div class="actions">
                    <button type="submit" class="btn btn-success">Save Borrow</button>
                    <button type="reset" class="btn btn-danger">Clear</button>
                </div>
            </form>

            <!-- Borrow Log -->
            <h3 style="margin:20px 0 12px;">Recent Borrows</h3>
            <table>
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
                        <th>Remarks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($borrows as $borrow)
                    <tr>
                        <td>{{ $borrow->user_id }}</td>
                        <td>{{ $borrow->borrower_name ?? '-' }}</td>
                        <td>{{ $borrow->uid }}</td>
                        <td>{{ $borrow->item->asset_id ?? '-' }}</td>
                        <td>{{ $borrow->borrow_date ? \Carbon\Carbon::parse($borrow->borrow_date)->format('Y-m-d') : '-' }}</td>
                        <td>{{ $borrow->due_date ? \Carbon\Carbon::parse($borrow->due_date)->format('Y-m-d') : '-' }}</td>
                        <td>{{ $borrow->borrowed_at }}</td>
                        <td>{{ $borrow->returned_at ? \Carbon\Carbon::parse($borrow->returned_at)->format('Y-m-d') : 'Not returned' }}</td>
                        <td>{{ $borrow->item->status ?? '-' }}</td>
                        <td>{{ $borrow->remarks ?? '-' }}</td>
                        <td>
                            @if(!$borrow->returned_at)
                                <form method="POST" action="{{ route('borrow.return', $borrow->uid) }}">
                                    @csrf
                                    <button class="btn btn-warning btn-sm">Return</button>
                                </form>
                            @else
                                <button class="btn btn-success btn-sm" disabled>Returned</button>
                            @endif

                            <form method="POST" action="{{ route('borrow.destroy', $borrow->id) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm" onclick="return confirm('Delete this borrow record?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center">No borrow records yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

<script>
async function fetchUser(cardUid) {
    try {
        const res = await fetch(`/api/borrow/user/${cardUid}`);
        if (!res.ok) throw new Error("User not found");
        const data = await res.json();
        document.getElementById('card_uid').value = data.uid || '';
        document.getElementById('user_id').value = data.student_id || '';
        document.getElementById('borrower_name').value = data.name || '';
    } catch (err) {
        alert(err.message);
    }
}

// Item UID input blur → fetch item info
async function fetchItem(itemUid) {
    try {
        const res = await fetch(`/borrow/fetch/${itemUid}`);
        if (!res.ok) throw new Error("Item not found");
        const data = await res.json();
        document.getElementById('asset_id').value = data.asset_id || '';
        document.getElementById('name').value = data.name || '';
        document.getElementById('status').value = data.status || '';
        document.getElementById('purchase_date').value = data.purchase_date || '';
    } catch (err) {
        alert(err.message);
    }
}

document.getElementById('item_uid').addEventListener('blur', function() {
    const uid = this.value.trim();
    if (uid) fetchItem(uid);
});

// Scan Card
document.getElementById('scanBtn').addEventListener('click', async () => {
    try {
        await fetch('/api/request-scan', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: 'card' })
        });

        alert("Please tap your user card...");

        let uid = null;
        for (let i = 0; i < 15; i++) {
            await new Promise(r => setTimeout(r, 1000));
            let res = await fetch('/api/read-uid');
            let data = await res.json();
            if (data.uid) { uid = data.uid; break; }
        }

        if (!uid) throw new Error("No card detected");

        document.getElementById('card_uid').value = uid;
        await fetchUser(uid);
    } catch (err) {
        alert("Error scanning card: " + err.message);
    }
});

// Scan Sticker
document.getElementById('scanStickerBtn').addEventListener('click', async () => {
    try {
        await fetch('/api/request-scan', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: 'sticker' })
        });

        alert("Please tap the item’s NFC sticker...");

        let uid = null;
        for (let i = 0; i < 15; i++) {
            await new Promise(r => setTimeout(r, 1000));
            let res = await fetch('/api/read-uid');
            let data = await res.json();
            if (data.uid) { uid = data.uid; break; }
        }

        if (!uid) throw new Error("No sticker detected");

        document.getElementById('item_uid').value = uid;

        let itemRes = await fetch(`/borrow/fetch/${uid}`);
        if (!itemRes.ok) throw new Error("Item not found in DB");
        let item = await itemRes.json();

        document.getElementById('asset_id').value       = item.asset_id || '';
        document.getElementById('name').value           = item.name || '';
        document.getElementById('status').value         = item.status || '';
        document.getElementById('purchase_date').value  = item.purchase_date || '';
        document.querySelector("textarea[name='remarks']").value = item.remarks || '';
    } catch (err) {
        alert("Error scanning sticker: " + err.message);
    }
});

</script>

<!-- ─────────────── Chat widget (added) ─────────────── -->
<div id="borrow-chat" class="chat-box">
    <div class="chat-header">
        <span>Borrow Chat</span>
        <button id="chatToggle" class="chat-min">Minimize</button>
    </div>
    <div id="chatBody" class="chat-body">
        <div id="chatMessages" class="chat-messages"></div>
        <form id="chatForm" class="chat-form">
            @csrf
            <input id="chatInput" class="chat-input" type="text" name="body" placeholder="Type a message…" autocomplete="off">
            <button type="submit" class="chat-send">Send</button>
        </form>
    </div>
</div>

<script>
// Borrow page chat (polling)
(() => {
    const ROOM = 'borrow';
    const CURRENT_USER_ID = @json(auth()->id());
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const CSRF = csrfMeta ? csrfMeta.content : '';

    const messagesEl = document.getElementById('chatMessages');
    const formEl     = document.getElementById('chatForm');
    const inputEl    = document.getElementById('chatInput');
    const toggleBtn  = document.getElementById('chatToggle');
    const chatBody   = document.getElementById('chatBody');

    let lastId = 0;
    let minimized = false;

    toggleBtn.addEventListener('click', () => {
        minimized = !minimized;
        chatBody.classList.toggle('hidden', minimized);
        toggleBtn.textContent = minimized ? 'Open' : 'Minimize';
    });

    function escapeHtml(str){
        return String(str).replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[s]));
    }

    function appendMsg(m){
        const mine = (CURRENT_USER_ID && Number(CURRENT_USER_ID) === Number(m.user_id));
        const row = document.createElement('div');
        row.className = 'chat-row';
        const time = new Date(m.created_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
        row.innerHTML = `
            <div class="chat-meta" style="text-align:${mine ? 'right' : 'left'}">
                <span style="font-weight:600;color:#374151;">${escapeHtml(m.user?.name ?? m.guest_name ?? 'Guest')}</span>
                <span style="margin-left:6px;">${time}</span>
            </div>
            <div style="text-align:${mine ? 'right' : 'left'}">
                <span class="chat-bubble">${escapeHtml(m.body)}</span>
            </div>
        `;
        messagesEl.appendChild(row);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    async function fetchNew(){
        try{
            const res = await fetch(`/chat/messages?room=${encodeURIComponent(ROOM)}&after=${lastId}`, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            if(!res.ok) return;
            const data = await res.json();
            if(Array.isArray(data) && data.length){
                data.forEach(m => appendMsg(m));
                lastId = data[data.length - 1].id;
            }
        } catch(e){ /* ignore */ }
    }

    formEl.addEventListener('submit', async (e) => {
        e.preventDefault();
        const body = inputEl.value.trim();
        if(!body) return;

        try{
            const res = await fetch('/chat/messages', {
                method: 'POST',
                credentials: 'same-origin', // include session cookie
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ body, room: ROOM })
            });

            if (!res.ok) {
                if (res.status === 401) { alert('Please log in to send messages.'); return; }
                if (res.status === 419) { alert('Page expired. Refresh and try again.'); return; }
                const txt = await res.text();
                console.error('Chat POST failed', res.status, txt);
                alert('Failed to send. Check console or logs.');
                return;
            }

            inputEl.value = '';
            await fetchNew(); // show immediately
        } catch(e){
            console.error(e);
        }
    });

    // Kickoff + poll
    fetchNew();
    setInterval(fetchNew, 3000);
})();
</script>
</body>
</html>
