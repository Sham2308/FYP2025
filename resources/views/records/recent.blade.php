<x-app-layout>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

        <h2 class="text-2xl font-bold mb-6">Borrow Records Overview</h2>

        <div class="bg-white shadow rounded-xl p-4 overflow-x-auto">
            <table class="min-w-full border border-gray-300 text-sm text-center">
                <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                    <tr>
                        <th class="px-3 py-2 border">Card ID</th>
                        <th class="px-3 py-2 border">Borrower</th>
                        <th class="px-3 py-2 border">User ID</th>
                        <th class="px-3 py-2 border">Item ID</th>
                        <th class="px-3 py-2 border">Borrow Date</th>
                        <th class="px-3 py-2 border">Return Date</th>
                        <th class="px-3 py-2 border">Borrowed At</th>
                        <th class="px-3 py-2 border">Returned At</th>
                        <th class="px-3 py-2 border">Status</th>
                        <th class="px-3 py-2 border">Remarks</th>
                        <th class="px-3 py-2 border">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($recent as $r)
                        <tr class="border hover:bg-gray-50">
                            <td class="border px-2 py-1">{{ $r['CardID'] ?? '-' }}</td>
                            <td class="border px-2 py-1">{{ $r['BorrowerName'] ?? '-' }}</td>
                            <td class="border px-2 py-1">{{ $r['UserID'] ?? '-' }}</td>
                            <td class="border px-2 py-1">{{ $r['ItemID'] ?? '-' }}</td>
                            <td class="border px-2 py-1">{{ $r['BorrowDate'] ?? '-' }}</td>
                            <td class="border px-2 py-1">{{ $r['ReturnDate'] ?? '-' }}</td>
                            <td class="border px-2 py-1">{{ $r['BorrowedAt'] ?? '-' }}</td>
                            <td class="border px-2 py-1">{{ $r['ReturnedAt'] ?? '-' }}</td>

                            {{-- ✅ Status with colors --}}
                            <td class="border px-2 py-1 font-semibold capitalize"
                                style="color:
                                    {{ strtolower($r['Status'] ?? '') === 'available' ? '#166534' :
                                        (strtolower($r['Status'] ?? '') === 'borrowed' ? '#991b1b' : '#92400e') }};
                                       background:
                                    {{ strtolower($r['Status'] ?? '') === 'available' ? '#dcfce7' :
                                        (strtolower($r['Status'] ?? '') === 'borrowed' ? '#fee2e2' : '#fef9c3') }};
                                       border-radius:6px;">
                                {{ $r['Status'] ?? '-' }}
                            </td>

                            <td class="border px-2 py-1">{{ $r['Remarks'] ?? '-' }}</td>

                            {{-- ✅ Action buttons --}}
                            <td class="border px-2 py-1" style="min-width:160px;">
                                {{-- ✅ Return button --}}
                                @if(($r['Status'] ?? '') !== 'Available' && !empty($r['ItemID']))
                                    <form action="{{ route('borrow.publicReturn', ['uid' => $r['ItemID']]) }}"
                                          method="POST" style="display:inline-block;">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-success btn-sm hover:brightness-110 transition">
                                            Returned
                                        </button>
                                    </form>
                                @else
                                    <button class="btn btn-success btn-sm opacity-60 cursor-not-allowed" disabled>
                                        Returned
                                    </button>
                                @endif

                                {{-- 🗑 Delete button --}}
                                <form action="{{ route('borrow.delete', ['rowIndex' => $r['RowNumber']]) }}"
                                      method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-danger btn-sm hover:brightness-110 transition">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-gray-500 py-3">No records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Inline Button Styles --}}
    <style>
        .btn {
            padding: 6px 10px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-success { background: #16a34a; color: #fff; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
    </style>
</x-app-layout>
