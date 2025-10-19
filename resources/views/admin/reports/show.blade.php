<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-4xl text-blue-700 leading-tight text-center">
            Report Submission Form — #{{ $report->id }}
        </h2>
    </x-slot>           

    <div class="max-w-3xl mx-auto py-10 px-6">
        <div class="bg-white shadow-lg rounded-2xl border border-gray-200 p-8">
            <form class="space-y-6">

                {{-- Section Title --}}
                <div class="border-b pb-2 mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Reporter Information</h3>
                </div>

                {{-- From Name --}}
                @php
                    $fromName  = data_get($report, 'user.name')  ?? $report->guest_name  ?? 'Guest';
                    $fromEmail = data_get($report, 'user.email') ?? $report->guest_email ?? null;
                @endphp
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Full Name</label>
                        <input type="text" value="{{ $fromName }}" readonly
                               class="w-full bg-gray-50 border border-gray-300 rounded-md px-4 py-2 text-gray-700">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
                        <input type="text" value="{{ $fromEmail ?? '—' }}" readonly
                               class="w-full bg-gray-50 border border-gray-300 rounded-md px-4 py-2 text-gray-700">
                    </div>
                </div>

                {{-- Submission Info --}}
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Date Submitted</label>
                        <input type="text" value="{{ $report->created_at->format('d M Y, h:i A') }}" readonly
                               class="w-full bg-gray-50 border border-gray-300 rounded-md px-4 py-2 text-gray-700">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Priority Level</label>
                        <input type="text" value="{{ ucfirst($report->priority ?? 'Normal') }}" readonly
                               class="w-full bg-yellow-50 border border-yellow-300 text-yellow-800 font-semibold rounded-md px-4 py-2">
                    </div>
                </div>

                {{-- Section Divider --}}
                <div class="border-b pb-2 mb-4 mt-6">
                    <h3 class="text-lg font-semibold text-gray-800">Report Details</h3>
                </div>

                {{-- Subject --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Subject</label>
                    <input type="text" value="{{ $report->subject ?? '—' }}" readonly
                           class="w-full bg-gray-50 border border-gray-300 rounded-md px-4 py-2 text-gray-700">
                </div>

                {{-- Description / Message --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Description / Message</label>
                    <textarea rows="5" readonly
                              class="w-full bg-gray-50 border border-gray-300 rounded-md px-4 py-2 text-gray-700 resize-none">{{ $report->message ?? '—' }}</textarea>
                </div>

                {{-- Related Item --}}
                @if($report->item)
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Related Item</label>
                        <input type="text" value="#{{ $report->item->id }} — {{ $report->item->name ?? 'Item' }}" readonly
                               class="w-full bg-gray-50 border border-gray-300 rounded-md px-4 py-2 text-gray-700">
                    </div>
                @endif

                {{-- Attachments --}}
                @if ($report->attachments && count($report->attachments))
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Attachments</label>
                        <div class="space-y-2">
                            @foreach ($report->attachments as $i => $path)
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-700">File {{ $i+1 }}</span>
                                    <a href="{{ route('admin.reports.attachment', [$report, $i]) }}"
                                       class="text-blue-600 text-sm underline hover:text-blue-800">
                                        Download
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </form>
        </div>
    </div>
</x-app-layout>
