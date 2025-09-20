<x-guest-layout>
    {{-- Remove the Laravel logo --}}
    <x-slot name="logo"></x-slot>

    <div class="max-w-2xl mx-auto px-6 py-10 font-sans text-gray-900">
        <h1 class="text-3xl font-bold mb-8">Register User</h1>

        <form method="POST" action="{{ route('register') }}" class="space-y-6">
            @csrf

            {{-- UID --}}
            <div>
                <label for="uid" class="block text-base mb-2">UID (Scanned)</label>
                <div class="flex">
                    <input id="uid" name="uid" type="text" value="{{ old('uid') }}" required
                           class="flex-1 h-11 border border-gray-300 px-3 rounded-l-md
                                  focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="button" id="scanBtn"
                            class="h-11 border border-l-0 border-gray-300 px-4 rounded-r-md
                                   text-blue-600 hover:bg-blue-50">
                        Scan Card
                    </button>
                </div>
                @error('uid')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Name --}}
            <div>
                <label for="name" class="block text-base mb-2">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required
                       class="w-full h-11 border border-gray-300 px-3 rounded-md
                              focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('name')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Staff / Student ID --}}
            <div>
                <label for="staff_id" class="block text-base mb-2">Student / Staff ID</label>
                <input id="staff_id" name="staff_id" type="text" value="{{ old('staff_id') }}"
                       class="w-full h-11 border border-gray-300 px-3 rounded-md
                              focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('staff_id')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Save / Cancel --}}
            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="h-11 px-6 rounded-md bg-green-600 text-white font-medium hover:bg-green-700">
                    Save
                </button>
                <a href="{{ url('/') }}"
                class="h-11 px-6 rounded-md bg-gray-500 text-white font-medium hover:bg-gray-600 flex items-center justify-center">
                    Cancel
                </a>
            </div>

        </form>
    </div>

    <script>
        // Example only: replace with your real NFC integration
        document.getElementById('scanBtn')?.addEventListener('click', () => {
            const el = document.getElementById('uid');
            if (el && !el.value) el.value = 'FAKE-UID-123456';
        });
    </script>
</x-guest-layout>
