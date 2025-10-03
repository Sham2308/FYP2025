<x-app-layout>
    {{-- Page header (appears under the blue nav) --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Technical Dashboard
        </h2>
    </x-slot>

    {{-- Scoped styles so they don't clash with global Tailwind --}}
    <style>
        .nfc-page * { box-sizing: border-box; }
        .nfc-page .card { border:1px solid #e5e7eb; border-radius:12px; padding:20px; background:#fff; }
        .nfc-page .alert { padding:10px 12px; border-radius:6px; margin:10px auto; width:95%; }
        .nfc-page .alert-success { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
        .nfc-page .alert-error { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
        .nfc-page table { border-collapse: collapse; width: 100%; margin-top:14px; }
        .nfc-page th, .nfc-page td { border: 1px solid #e5e7eb; padding: 8px; text-align: center; }
        .nfc-page th { background: #f8fafc; }
        .nfc-page .badge { padding:4px 10px; border-radius:999px; font-weight:600; display:inline-block; }
        .nfc-page .badge-good { background:#dcfce7; color:#166534; border:1px solid #86efac; }
        .nfc-page .badge-na   { background:#e5e7eb; color:#374151; border:1px solid #d1d5db; }
    </style>

    <div class="nfc-page mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 mt-6 space-y-6">
        {{-- Welcome card --}}
        @php $user = auth()->user(); @endphp
        <section class="card">
            <p class="text-sm text-gray-700">
                Welcome, {{ $user?->name ?? 'Tech User' }}
                <span class="text-gray-500">({{ $user?->role ?? 'technical' }})</span>
            </p>
        </section>

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        {{-- Status Overview (KPI row + chart) --}}
        <section class="card">
            @php
                $total = max(1,
                  ($counts['borrowed']  ?? 0) +
                  ($counts['returned']  ?? 0) +
                  ($counts['stolen']    ?? 0) +
                  ($counts['available'] ?? 0) +
                  ($counts['repair']    ?? 0)
                );
                $pct = fn($n) => (int) round(($n / $total) * 100);

                $pBorrowed  = $pct($counts['borrowed']  ?? 0);
                $pReturned  = $pct($counts['returned']  ?? 0);
                $pStolen    = $pct($counts['stolen']    ?? 0);
                $pAvailable = $pct($counts['available'] ?? 0);
                $pRepair    = $pct($counts['repair']    ?? 0);
            @endphp

            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold">Asset Status Overview</h2>
                <span class="text-xs text-gray-500">Source: Google Sheet + DB reconciled</span>
            </div>

            <div class="flex gap-3 overflow-x-auto pb-2 -mx-2 px-2">
                <div class="shrink-0 w-1"></div>

                {{-- Borrowed (changed to blue theme) --}}
                <div class="snap-start shrink-0 w-[220px] rounded-xl bg-blue-50 ring-1 ring-blue-100 p-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-blue-600 text-lg"></span>
                            <div class="leading-tight">
                                <p class="text-xs font-medium text-blue-800">Borrowed</p>
                                <p class="text-xl font-bold tabular-nums">{{ $counts['borrowed'] ?? 0 }}</p>
                            </div>
                        </div>
                        <div class="relative h-10 w-10">
                            <div class="absolute inset-0 rounded-full" style="background: conic-gradient(#1d4ed8 {{ $pBorrowed * 3.6 }}deg, #e5e7eb 0deg)"></div>
                            <div class="absolute inset-[3px] flex items-center justify-center rounded-full bg-white text-[10px] font-bold text-blue-700">
                                {{ $pBorrowed }}%
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Returned --}}
                <div class="snap-start shrink-0 w-[220px] rounded-xl bg-green-50 ring-1 ring-green-100 p-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-green-600 text-lg"></span>
                            <div class="leading-tight">
                                <p class="text-xs font-medium text-green-800">Returned</p>
                                <p class="text-xl font-bold tabular-nums">{{ $counts['returned'] ?? 0 }}</p>
                            </div>
                        </div>
                        <div class="relative h-10 w-10">
                            <div class="absolute inset-0 rounded-full" style="background: conic-gradient(#16a34a {{ $pReturned * 3.6 }}deg, #e5e7eb 0deg)"></div>
                            <div class="absolute inset-[3px] flex items-center justify-center rounded-full bg-white text-[10px] font-bold text-green-700">
                                {{ $pReturned }}%
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Stolen --}}
                <div class="snap-start shrink-0 w-[220px] rounded-xl bg-red-50 ring-1 ring-red-100 p-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-red-600 text-lg"></span>
                            <div class="leading-tight">
                                <p class="text-xs font-medium text-red-800">Stolen</p>
                                <p class="text-xl font-bold tabular-nums">{{ $counts['stolen'] ?? 0 }}</p>
                            </div>
                        </div>
                        <div class="relative h-10 w-10">
                            <div class="absolute inset-0 rounded-full" style="background: conic-gradient(#dc2626 {{ $pStolen * 3.6 }}deg, #e5e7eb 0deg)"></div>
                            <div class="absolute inset-[3px] flex items-center justify-center rounded-full bg-white text-[10px] font-bold text-red-700">
                                {{ $pStolen }}%
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Available --}}
                <div class="snap-start shrink-0 w-[220px] rounded-xl bg-yellow-50 ring-1 ring-yellow-100 p-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-yellow-600 text-lg"></span>
                            <div class="leading-tight">
                                <p class="text-xs font-medium text-yellow-800">Available</p>
                                <p class="text-xl font-bold tabular-nums">{{ $counts['available'] ?? 0 }}</p>
                            </div>
                        </div>
                        <div class="relative h-10 w-10">
                            <div class="absolute inset-0 rounded-full" style="background: conic-gradient(#f59e0b {{ $pAvailable * 3.6 }}deg, #e5e7eb 0deg)"></div>
                            <div class="absolute inset-[3px] flex items-center justify-center rounded-full bg-white text-[10px] font-bold text-yellow-700">
                                {{ $pAvailable }}%
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Under Repair --}}
                <div class="snap-start shrink-0 w-[220px] rounded-xl bg-purple-50 ring-1 ring-purple-100 p-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-purple-600 text-lg"></span>
                            <div class="leading-tight">
                                <p class="text-xs font-medium text-purple-800">Under Repair</p>
                                <p class="text-xl font-bold tabular-nums">{{ $counts['repair'] ?? 0 }}</p>
                            </div>
                        </div>
                        <div class="relative h-10 w-10">
                            <div class="absolute inset-0 rounded-full" style="background: conic-gradient(#7c3aed {{ $pRepair * 3.6 }}deg, #e5e7eb 0deg)"></div>
                            <div class="absolute inset-[3px] flex items-center justify-center rounded-full bg-white text-[10px] font-bold text-purple-700">
                                {{ $pRepair }}%
                            </div>
                        </div>
                    </div>
                </div>

                <div class="shrink-0 w-1"></div>
            </div>

            {{-- Chart --}}
            <div class="mt-5 relative" style="height: 320px;">
                <canvas id="assetPieChart" aria-label="Asset status distribution" role="img"></canvas>
                <p id="noDataNotice" class="mt-2 hidden text-center text-sm text-gray-500">
                    No status column detected or totals are zero.
                </p>
            </div>
        </section>

        {{-- Under Repair (DB) --}}
        <section class="card">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">Under Repair (DB)</h2>
                <span class="text-xs text-gray-500">Source: items table</span>
            </div>

            @if(($borrowItems ?? collect())->isEmpty())
                <p class="text-gray-500">No items currently marked <b>under repair</b> in the database.</p>
            @else
                <div class="overflow-x-auto rounded-xl border">
                    <table class="min-w-full border-collapse text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="border px-4 py-2 text-left font-semibold text-gray-700">Asset ID</th>
                                <th class="border px-4 py-2 text-left font-semibold text-gray-700">Name</th>
                                <th class="border px-4 py-2 text-left font-semibold text-gray-700">Status</th>
                                <th class="border px-4 py-2 text-left font-semibold text-gray-700">Remarks</th>
                                <th class="border px-4 py-2 text-left font-semibold text-gray-700">Updated</th>
                                <th class="border px-4 py-2 text-left font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($borrowItems as $item)
                                @php
                                    $label = method_exists($item, 'getStatusLabelAttribute') ? $item->status_label : ucfirst($item->status);
                                @endphp
                                <tr class="odd:bg-white even:bg-gray-50 hover:bg-blue-50/50">
                                    <td class="border px-4 py-2 text-left font-medium">{{ $item->asset_id }}</td>
                                    <td class="border px-4 py-2 text-left">{{ $item->name }}</td>
                                    <td class="border px-4 py-2">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-medium ring-1 bg-purple-100 text-purple-700 ring-purple-200">
                                            {{ $label }}
                                        </span>
                                    </td>
                                    <td class="border px-4 py-2 text-left">{{ $item->remarks }}</td>
                                    <td class="border px-4 py-2 text-left">
                                        {{ optional($item->updated_at)->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                                    </td>
                                    <td class="border px-4 py-2 text-left">
                                        {{-- Button: mark available --}}
                                        <form
                                            action="{{ route('items.markAvailable', $item->asset_id) }}"
                                            method="POST"
                                            onsubmit="this.querySelector('button[type=submit]').disabled=true"
                                        >
                                            @csrf
                                            @method('PATCH')
                                            <button
                                                type="submit"
                                                class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50"
                                                title="Mark this item as available (repair completed)"
                                            >
                                                Mark as Available
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    {{-- Chart.js (CDN) just for this page --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    (function () {
      const ctx = document.getElementById('assetPieChart');
      const noData = document.getElementById('noDataNotice');

      const dataBorrowed = Number("{{ $counts['borrowed'] ?? 0 }}");
      const dataReturned = Number("{{ $counts['returned'] ?? 0 }}");
      const dataStolen   = Number("{{ $counts['stolen']   ?? 0 }}");
      const dataAvail    = Number("{{ $counts['available']?? 0 }}");
      const dataRepair   = Number("{{ $counts['repair']   ?? 0 }}");

      const values = [dataBorrowed, dataReturned, dataStolen, dataAvail, dataRepair];
      const total = values.reduce((a,b)=>a+b, 0);

      if (!total) {
        noData.classList.remove('hidden');
        if (ctx) ctx.style.display = 'none';
        return;
      }

      const BLUE = '#1d4ed8'; // updated blue to match your nav

      new Chart(ctx, {
        type: 'pie',
        data: {
          labels: ['Borrowed', 'Returned', 'Stolen', 'Available', 'Under Repair'],
          datasets: [{
            data: values,
            backgroundColor: [BLUE,'#16a34a','#dc2626','#f59e0b','#7c3aed'],
            borderWidth: 0
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom' },
            tooltip: { callbacks: { label: (c) => `${c.label}: ${c.formattedValue}` } }
          }
        }
      });
    })();

    // --- Inject logo beside "TapNBorrow" in the top nav (no other files touched) ---
    document.addEventListener('DOMContentLoaded', function () {
      const anchors = Array.from(document.querySelectorAll('nav a, header a'));
      const brand = anchors.find(a => a.textContent.trim() === 'TapNBorrow');
      if (brand && !brand.querySelector('img[data-brand-logo]')) {
        const img = document.createElement('img');
        img.src = "{{ asset('images/icon-logo.png') }}";
        img.alt = "TapNBorrow";
        img.setAttribute('data-brand-logo', '1');
        img.style.width = '22px';
        img.style.height = '22px';
        img.style.display = 'inline-block';
        img.style.marginRight = '8px';
        img.style.verticalAlign = 'middle';
        brand.style.display = 'inline-flex';
        brand.style.alignItems = 'center';
        brand.style.gap = '8px';
        brand.prepend(img);
      }
    });
    </script>
</x-app-layout>
