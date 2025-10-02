<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Report #{{ $report->id }}
        </h2>
    </x-slot>

    <div class="max-w-5xl mx-auto py-6 px-4 space-y-4">
        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 p-3 rounded">{{ session('success') }}</div>
        @endif

        {{-- Meta + message --}}
        <div class="bg-white p-6 rounded shadow">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold">{{ $report->subject }}</h3>
                    @php
                        $fromName  = data_get($report, 'user.name')  ?? $report->guest_name  ?? 'Guest';
                        $fromEmail = data_get($report, 'user.email') ?? $report->guest_email ?? null;
                    @endphp
                    <p class="text-sm text-gray-600">
                        From: <strong>{{ $fromName }}</strong>
                        @if($fromEmail) ({{ $fromEmail }}) @endif
                        • {{ $report->created_at->format('Y-m-d H:i') }}
                    </p>
                </div>
                <div class="text-right space-y-1">
                    <span class="inline-block text-xs px-2 py-1 rounded bg-gray-100">Priority: {{ ucfirst($report->priority) }}</span>
                    <span class="inline-block text-xs px-2 py-1 rounded bg-gray-100">Status: {{ ucfirst(str_replace('_',' ',$report->status)) }}</span>
                </div>
            </div>

            <div class="mt-4 prose max-w-none">
                <p class="whitespace-pre-line">{{ $report->message }}</p>
            </div>

            @if($report->item)
                <div class="mt-4 text-sm text-gray-700">
                    Related item: #{{ $report->item->id }} — {{ $report->item->name ?? 'Item' }}
                </div>
            @endif

            @if ($report->attachments && count($report->attachments))
                <div class="mt-4">
                    <h4 class="font-semibold">Attachments</h4>
                    <ul class="list-disc ml-6">
                        @foreach ($report->attachments as $i => $path)
                            <li>
                                <a class="text-indigo-600 hover:underline"
                                   href="{{ route('admin.reports.attachment', [$report, $i]) }}">
                                    Download file {{ $i+1 }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- Update status --}}
        <div class="bg-white p-6 rounded shadow">
            <form method="POST" action="{{ route('admin.reports.updateStatus', $report) }}" class="flex items-end gap-3">
                @csrf @method('PATCH')
                <div>
                    <label class="block text-sm font-medium">Change status</label>
                    <select name="status" class="mt-1 border rounded p-2">
                        @foreach (['open','in_progress','closed'] as $s)
                            <option value="{{ $s }}" @selected($report->status===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="px-4 py-2 bg-gray-800 text-white rounded">Update</button>
            </form>
        </div>
    </div>
</x-app-layout>
