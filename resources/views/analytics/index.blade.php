<x-app-layout>
    <div class="max-w-5xl mx-auto py-10 px-6">
        {{-- Title --}}
        <h1 class="font-semibold text-4xl text-blue-700 leading-tight text-center">
            Recent Reports
        </h1>

        @forelse($latestReports as $report)
            <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4 hover:bg-gray-50 transition-all">
                <div class="flex justify-between items-start">
                    <div>
                        {{-- Report Title --}}
                        <p class="text-lg font-semibold text-gray-800">
                            {{ $report->subject ?? 'Untitled Report' }}
                        </p>

                        {{-- Description --}}
                        <p class="text-base text-gray-600 mt-2 leading-relaxed">
                            {{ Str::limit($report->description ?? '', 120) }}
                        </p>

                        {{-- Submitted Date --}}
                        <p class="text-sm text-gray-400 mt-2">
                            Submitted: {{ \Carbon\Carbon::parse($report->created_at)->format('d M Y, h:i A') }}
                        </p>
                    </div>

                    {{-- View Details --}}
                    <a href="{{ route('admin.reports.show', $report->id) }}"
                       class="text-blue-600 text-base font-medium hover:underline">
                        View Details →
                    </a>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-6 text-center text-gray-500 text-lg">
                No recent reports yet.
            </div>
        @endforelse
    </div>
</x-app-layout>
