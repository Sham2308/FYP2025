{{-- resources/views/technical/dashboard.blade.php --}}
@php
    /** @var \App\Models\User $user */
    $user = auth()->user();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Technical Dashboard — TapNBorrow</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900">
    {{-- Top bar --}}
    <header class="bg-blue-600">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
            <div>
                <a href="{{ url('/') }}" class="text-lg font-semibold tracking-tight text-white">
                    <span>Tap</span><span class="font-bold">NBorrow</span>
                </a>
            </div>

            <div class="flex items-center gap-4">
                <span class="hidden sm:inline text-sm text-white">
                    {{ ucfirst($user->role ?? 'technical') }}
                </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="rounded-lg bg-red-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                    >
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- Page header --}}
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h1 class="text-xl sm:text-2xl font-semibold mt-6">Technical Dashboard</h1>
    </div>

    {{-- Content --}}
    <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 mt-6 space-y-6">
        {{-- Welcome card --}}
        <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
            <p class="text-sm text-gray-700">
                Welcome, {{ $user?->name ?? 'Tech User' }}
                <span class="text-gray-500">({{ $user->role ?? 'technical' }})</span>
            </p>
        </section>

        {{-- Borrow Items (rendered like the Inventory table) --}}
        <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">Borrow Items</h2>
                <a href="{{ env('BORROW_SHEET_CSV') ? str_replace('output=csv', 'output=html', env('BORROW_SHEET_CSV')) : '#' }}"
                   target="_blank"
                   class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-sm hover:bg-blue-700">
                   Open sheet (web)
                </a>
            </div>

            @if(empty($headers))
                <p class="text-gray-500">No data loaded. Make sure the sheet is <b>Published to web</b> and the
                    <code>BORROW_SHEET_CSV</code> env is set.</p>
            @else
                <div class="overflow-x-auto rounded-xl border">
                    <table class="min-w-full border-collapse text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                @foreach($headers as $h)
                                    <th class="border px-4 py-2 text-left font-semibold text-gray-700">{{ $h }}</th>
                                @endforeach
                                <th class="border px-4 py-2 text-left font-semibold text-gray-700">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $r)
                                @php
                                    // Map by header for easier styling (handles different column orders)
                                    $row = [];
                                    foreach ($headers as $i=>$h) { $row[$h] = $r[$i] ?? ''; }

                                    $status = $row['Status'] ?? $row['Action'] ?? ''; // some sheets use Status, some Action
                                    $statusLower = strtolower($status);
                                    $badgeClasses =
                                        str_contains($statusLower, 'available') || str_contains($statusLower, 'returned')
                                            ? 'bg-green-100 text-green-700 ring-green-200'
                                            : (str_contains($statusLower, 'borrow') || str_contains($statusLower, 'out')
                                                ? 'bg-yellow-100 text-yellow-700 ring-yellow-200'
                                                : 'bg-gray-100 text-gray-700 ring-gray-200');
                                @endphp
                                <tr class="odd:bg-white even:bg-gray-50 hover:bg-blue-50/50">
                                    @foreach($headers as $h)
                                        @if(in_array(strtolower($h), ['status','action']))
                                            <td class="border px-4 py-2">
                                                <span class="px-2.5 py-1 rounded-full text-xs font-medium ring-1 {{ $badgeClasses }}">
                                                    {{ $row[$h] }}
                                                </span>
                                            </td>
                                        @else
                                            <td class="border px-4 py-2">{{ $row[$h] }}</td>
                                        @endif
                                    @endforeach

                                    {{-- Local "Delete" button (front-end only demo) --}}
                                    <td class="border px-4 py-2">
                                        <button
                                            type="button"
                                            onclick="alert('This button is a demo for the sheet view. Real deletes should be done in your app or in the sheet workflow.')"
                                            class="rounded-lg bg-red-600 px-3 py-1.5 text-white text-xs font-semibold hover:bg-red-700">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    Source: Google Sheet (Published to web → CSV). Update <code>BORROW_SHEET_CSV</code> to change tab or range.
                </p>
            @endif
        </section>
    </main>

    <div class="h-10"></div>
</body>
</html>
