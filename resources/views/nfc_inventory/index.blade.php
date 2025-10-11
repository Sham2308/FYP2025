<x-app-layout>
    {{-- Optional page-scoped styles (no body/html selectors) --}}
    <style>
        .tech-page * { box-sizing: border-box; }
        .tech-page h2 { text-align:center; margin:24px 0 6px; }
        .tech-page .wrap { width:95%; margin: 0 auto 40px; }
        .tech-page .card { border:1px solid #e5e7eb; border-radius:8px; padding:12px 16px; margin-top:12px; background:#ffffff; }
        .tech-page .alert { padding:10px 12px; border-radius:6px; margin:10px auto; width:95%; }
        .tech-page .alert-success { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
        .tech-page .alert-error { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
        .tech-page table { border-collapse: collapse; width: 100%; margin-top:14px; }
        .tech-page th, .tech-page td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        .tech-page th { background: #f5f5f5; }
        .tech-page .badge { padding:4px 10px; border-radius:999px; font-weight:600; display:inline-block; }
        .tech-page .badge-good { background:#dcfce7; color:#166534; border:1px solid #86efac; }
        .tech-page .badge-na   { background:#e5e7eb; color:#374151; border:1px solid #d1d5db; }
        .tech-page .badge-warn { background:#ede9fe; color:#5b21b6; border:1px solid #ddd6fe; } /* Under Repair look */
        .tech-page .top-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:8px; }
        .tech-page .btn { padding:8px 14px; border:none; border-radius:8px; font-weight:600; cursor:pointer; }
        .tech-page .btn-green { background:#16a34a; color:#fff; }
        .tech-page .btn-red { background:#dc2626; color:#fff; }
        .tech-page .btn-red:hover { background:#b91c1c; }
        .tech-page .btn-purple { background:#7c3aed; color:#fff; }
        .tech-page .form-row { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px; }
        .tech-page .form-row input, .tech-page .form-row select { flex:1; min-width:160px; padding:8px; border:1px solid #cbd5e1; border-radius:6px; }
        .tech-page .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:50; }
        .tech-page .modal-content { background:#fff; padding:20px; border-radius:8px; width:90%; max-width:800px; }
        .tech-page .actions { display:flex; gap:8px; justify-content:center; flex-wrap:wrap; }

        /* Dropdown styles */
        .edit-dropdown { position:relative; display:inline-block; }
        .edit-btn { background:#2563eb; color:#fff; border:none; padding:8px 12px; border-radius:10px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:8px; }
        .dd-menu { position:absolute; right:0; top:42px; min-width:200px; background:#fff; border:1px solid #e5e7eb; border-radius:10px; box-shadow:0 10px 25px rgba(0,0,0,.08); padding:6px; display:none; z-index:20; text-align:left; }
        .dd-item, .dd-form-btn { display:block; width:100%; padding:10px 12px; border-radius:8px; text-decoration:none; color:#111; background:transparent; border:none; text-align:left; cursor:pointer; }
        .dd-item:hover, .dd-form-btn:hover { background:#f3f4f6; }
        .dd-danger { color:#b91c1c; }

        /* Filter form */
        .filter-form { display:flex; gap:10px; margin:10px 0 14px; flex-wrap:wrap; align-items:center; }
        .filter-form input, .filter-form select { padding:8px; border:1px solid #cbd5e1; border-radius:6px; }
        .filter-form button { padding:8px 14px; border:none; border-radius:6px; cursor:pointer; font-weight:600; }
        .filter-form .btn-blue { background:#2563eb; color:white; }
        .filter-form .btn-blue:hover { background:#1d4ed8; }
        .filter-form .btn-gray { background:#6b7280; color:white; text-decoration:none; display:inline-block; }
        .filter-form .btn-gray:hover { background:#4b5563; }
    </style>

    {{-- Optional page header slot (shows under the blue nav) --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            NFC Inventory Dashboard
        </h2>
    </x-slot>

    <div class="tech-page">
        {{-- Flash messages --}}
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <div class="wrap">
            <!-- Top actions -->
            <div class="card">
                <div class="top-actions">
                    <form action="{{ route('items.import.google') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="btn btn-green">Import from Google Sheets</button>
                    </form>

                    <button id="openModal" class="btn btn-green">+ Add Item</button>
                </div>
            </div>

            <!-- Add Item Modal -->
            <div id="itemModal" class="modal">
                <div class="modal-content">
                    <h3>Add New Item</h3>
                    <form method="POST" action="{{ route('items.store') }}">
                        @csrf
                        <div class="form-row">
                            <div style="flex:1; display:flex; gap:6px;">
                                <input type="text" id="uid" name="uid" placeholder="UID" required readonly>
                                <button type="button" id="scan-btn" class="btn btn-green">Scan Sticker</button>
                            </div>
                            <input type="text" name="asset_id" placeholder="Asset ID" required>
                            <input type="text" name="name" placeholder="Name" required>
                            <input type="text" name="detail" placeholder="Detail">
                        </div>
                        <div class="form-row">
                            <input type="text" name="accessories" placeholder="Accessories">
                            <input type="text" name="type_id" placeholder="Type ID">
                            <input type="text" name="serial_no" placeholder="Serial No">
                        </div>
                        <div class="form-row">
                            <input type="date" name="purchase_date">
                            <input type="text" name="remarks" placeholder="Remarks">
                            <select name="status" required>
                                <option value="available">Available</option>
                                <option value="borrowed">Borrowed</option>
                                <option value="retire">Retire</option>
                                <option value="under repair">Under Repair</option>
                                <option value="stolen">Stolen</option>
                                <option value="missing/lost">Missing/Lost</option>
                            </select>
                        </div>
                        <div class="form-row" style="justify-content:flex-end;">
                            <button type="button" id="closeModal" class="btn btn-red">Cancel</button>
                            <button type="submit" class="btn btn-green">Save</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Inventory Items Table -->
            <h3 style="margin-top:20px;">Inventory Items</h3>

            <!-- 🔍 Filter Form Added Here -->
            <form method="GET" action="{{ route('nfc.inventory') }}" class="filter-form" id="filterForm">
                <input type="text" name="search" id="searchInput" placeholder="Search by name, UID, or asset ID..." value="{{ request('search') }}">
                <select name="status" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="available" {{ request('status')=='available' ? 'selected' : '' }}>Available</option>
                    <option value="borrowed" {{ request('status')=='borrowed' ? 'selected' : '' }}>Borrowed</option>
                    <option value="retire" {{ request('status')=='retire' ? 'selected' : '' }}>Retire</option>
                    <option value="under repair" {{ request('status')=='under repair' ? 'selected' : '' }}>Under Repair</option>
                    <option value="stolen" {{ request('status')=='stolen' ? 'selected' : '' }}>Stolen</option>
                    <option value="missing/lost" {{ request('status')=='missing/lost' ? 'selected' : '' }}>Missing/Lost</option>
                </select>
                <button type="submit" class="btn btn-blue">Filter</button>
                <a href="{{ route('nfc.inventory') }}" class="btn btn-gray">Clear</a>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>UID</th>
                        <th>Asset_ID</th>
                        <th>Name</th>
                        <th>Detail</th>
                        <th>Accessories</th>
                        <th>Type_ID</th>
                        <th>Serial No</th>
                        <th>Status</th>
                        <th>Purchase Date</th>
                        <th>Remarks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php
                            $status = (string)($item->status ?? '');
                            $isAvailable = $status === 'available';
                            $isUnderRepair = $status === 'under repair';
                        @endphp
                        <tr>
                            <td>{{ $item->uid ?? '—' }}</td>
                            <td>{{ $item->asset_id ?? '—' }}</td>
                            <td>{{ $item->name ?? '—' }}</td>
                            <td>{{ $item->detail ?? '—' }}</td>
                            <td>{{ $item->accessories ?? '—' }}</td>
                            <td>{{ $item->type_id ?? '—' }}</td>
                            <td>{{ $item->serial_no ?? '—' }}</td>
                            <td>
                                @if($isAvailable)
                                    <span class="badge badge-good">Available</span>
                                @elseif($isUnderRepair)
                                    <span class="badge badge-warn">Under Repair</span>
                                @else
                                    <span class="badge badge-na">{{ $status ?: '—' }}</span>
                                @endif
                            </td>
                            <td>{{ $item->purchase_date ?? '—' }}</td>
                            <td>{{ $item->remarks ?? '—' }}</td>
                            <td>
                                <div class="edit-dropdown">
                                    <button class="edit-btn" type="button" data-dd="menu-{{ $item->asset_id }}">
                                        Edit
                                        <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                    <div id="menu-{{ $item->asset_id }}" class="dd-menu">
                                        <a class="dd-item" href="{{ route('items.edit', $item->asset_id) }}"> Edit details</a>

                                        @if($isAvailable)
                                            <form method="POST" action="{{ route('items.markUnderRepair', $item->asset_id) }}" style="margin:0;">
                                                @csrf
                                                @method('PATCH')
                                                <button class="dd-form-btn" type="submit"
                                                    onclick="return confirm('Mark this item as Under Repair?')">
                                                     Mark as Under Repair
                                                </button>
                                            </form>
                                        @elseif($isUnderRepair)
                                            <form method="POST" action="{{ route('items.markAvailable', $item->asset_id) }}" style="margin:0;">
                                                @csrf
                                                @method('PATCH')
                                                <button class="dd-form-btn" type="submit"
                                                    onclick="return confirm('Mark this item as Available?')">
                                                    ✅ Mark as Available
                                                </button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('items.destroy', $item->asset_id) }}" style="margin:0;">
                                            @csrf
                                            @method('DELETE')
                                            <button class="dd-form-btn dd-danger" type="submit"
                                                onclick="return confirm('Delete this item? This cannot be undone.')">
                                                 Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12">No items yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Page script --}}
    <script>
    // === Filter auto-submit ===
    document.getElementById('statusFilter')?.addEventListener('change', () => {
        document.getElementById('filterForm').submit();
    });
    document.getElementById('searchInput')?.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('filterForm').submit();
        }
    });

    // === Rest of your existing JS (unchanged) ===
    const openBtn = document.getElementById("openModal");
    const closeBtn = document.getElementById("closeModal");
    const modal = document.getElementById("itemModal");

    if (openBtn) openBtn.onclick = () => { modal.style.display = "flex"; };
    if (closeBtn) closeBtn.onclick = () => { modal.style.display = "none"; };
    window.onclick = (e) => { if (e.target === modal) modal.style.display = "none"; };

    const scanBtn = document.getElementById("scan-btn");
    if (scanBtn) {
        scanBtn.addEventListener("click", async () => {
            try {
                await fetch("/api/request-scan", { method: "POST" });
                alert("Please tap your NFC card...");

                let uid = null;
                for (let i = 0; i < 15; i++) {
                    const response = await fetch("/api/read-uid");
                    const data = await response.json();
                    if (data.uid) { uid = data.uid; break; }
                    await new Promise(r => setTimeout(r, 1000));
                }

                if (uid) {
                    document.getElementById("uid").value = uid;
                } else {
                    alert("No UID received. Try again.");
                }
            } catch (err) {
                alert("Error: " + err);
            }
        });
    }

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.edit-dropdown')) {
            document.querySelectorAll('.dd-menu').forEach(m => m.style.display = 'none');
            return;
        }
        const btn = e.target.closest('button[data-dd]');
        if (btn) {
            const id = btn.getAttribute('data-dd');
            const menu = document.getElementById(id);
            const isOpen = menu && menu.style.display === 'block';
            document.querySelectorAll('.dd-menu').forEach(m => m.style.display = 'none');
            if (menu) menu.style.display = isOpen ? 'none' : 'block';
        }
    });
    </script>
</x-app-layout>
