<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Reports
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-6 px-4 space-y-4">
        {{-- Filters --}}
        <form method="GET" class="bg-white p-4 rounded shadow grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search subject/message" class="border rounded p-2">
            <select name="priority" class="border rounded p-2">
                <option value="">All Priority</option>
                @foreach (['low','medium','high'] as $p)
                    <option value="{{ $p }}" @selected(request('priority')===$p)>{{ ucfirst($p) }}</option>
                @endforeach
            </select>
            <button class="bg-gray-800 text-white rounded px-3">Filter</button>
        </form>

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 p-3 rounded">{{ session('success') }}</div>
        @endif

        {{-- Table --}}
        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left p-3">Subject</th>
                        <th class="text-left p-3">From</th>
                        <th class="text-left p-3">Priority</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Date</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reports as $r)
                        <tr class="border-t">
                            <td class="p-3">{{ $r->subject }}</td>
                            <td class="p-3">{{ data_get($r, 'user.name') ?? $r->guest_name ?? 'Guest' }}</td>
                            <td class="p-3 capitalize">{{ $r->priority }}</td>
                            <td class="p-3">{{ $r->created_at->format('d-m-Y H:i') }}</td>
                            <td class="p-3">
                                <a href="{{ route('admin.reports.show', $r) }}" class="text-indigo-600 hover:underline">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="p-3" colspan="6">No reports found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div>{{ $reports->links() }}</div>
    </div>
</x-app-layout>
