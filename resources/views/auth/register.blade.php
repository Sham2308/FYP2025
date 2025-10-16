<x-guest-layout>
    <x-slot name="logo"></x-slot>

    <div class="fixed inset-0 bg-[#0f172a]"></div>

    <div class="relative z-10 min-h-screen flex items-center justify-center">
        <div class="w-full" style="max-width: 480px;">
            <div class="bg-white shadow-xl rounded-2xl p-8">

                <h1 class="text-4xl font-bold mb-8 text-center text-blue-600">
                    Register User
                </h1>

                <form method="POST" action="{{ route('register-user.store') }}" class="space-y-6">
                    @csrf

                    {{-- UID --}}
                    <div>
                        <label for="uid" class="block text-base text-blue-600 mb-2">UID (Scanned)</label>
                        <div class="flex">
                            <input
                                id="uid"
                                name="uid"
                                type="text"
                                value="{{ old('uid') }}"
                                readonly
                                required
                                autocomplete="off"
                                class="flex-1 h-11 border border-gray-300 px-3 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 placeholder-gray-500"
                            >
                            <button
                                type="button"
                                id="scanBtn"
                                class="h-11 px-4 rounded-r-md bg-blue-600 text-white font-medium hover:bg-blue-700"
                            >
                                Scan Card
                            </button>
                        </div>
                        <p id="scanStatus" class="text-sm text-gray-500 mt-2"></p>
                    </div>

                    {{-- Name --}}
                    <div>
                        <label for="name" class="block text-base text-blue-600 mb-2">Name</label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name') }}"
                            required
                            autocomplete="name"
                            class="w-full h-11 border border-gray-300 px-3 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 placeholder-gray-500"
                        >
                    </div>

                    {{-- Student / Staff ID --}}
                    <div>
                        <label for="staff_id" class="block text-base text-blue-600 mb-2">Student / Staff ID</label>
                        <input
                            id="staff_id"
                            name="staff_id"
                            type="text"
                            value="{{ old('staff_id') }}"
                            autocomplete="off"
                            class="w-full h-11 border border-gray-300 px-3 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 placeholder-gray-500"
                        >
                    </div>

                    {{-- Buttons --}}
                    <div class="flex gap-3 pt-2">
                        <button
                            type="submit"
                            class="h-11 px-6 rounded-md bg-green-600 text-white font-medium hover:bg-green-700"
                        >
                            Save
                        </button>
                        <a
                            href="{{ url('/borrow') }}"
                            class="h-11 px-6 rounded-md bg-gray-600 text-white font-medium hover:bg-gray-700 flex items-center justify-center"
                        >
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const scanBtn = document.getElementById('scanBtn');
        const uidField = document.getElementById('uid');
        const statusText = document.getElementById('scanStatus');

        scanBtn.addEventListener('click', async () => {
            statusText.innerHTML = "🟡 Waiting for card scan...";
            uidField.value = "";

            try {
                // 🔹 Tell Laravel to expect a scan (POST)
                const req = await fetch("/api/request-scan" , {
                    method: "POST",
                    headers: { "Content-Type": "application/json" }
                });
                const msg = await req.json();
                console.log("Request-scan response:", msg);
            } catch (err) {
                console.error("Error requesting scan:", err);
                statusText.innerHTML = "❌ Failed to request scan.";
                return;
            }

            // 🔹 Poll for UID (max 20s)
            let uid = null;
            for (let i = 0; i < 20; i++) {
                await new Promise(r => setTimeout(r, 1000));
                try {
                    const res = await fetch("/api/read-uid");
                    const data = await res.json();
                    console.log("Polling:", data);
                    if (data.uid) {
                        uid = data.uid;
                        break;
                    }
                } catch (err) {
                    console.error("Poll error:", err);
                }
            }

            if (uid) {
                uidField.value = uid;
                statusText.innerHTML = "✅ Card detected successfully!";
            } else {
                statusText.innerHTML = "❌ No card detected. Try again.";
            }
        });
    </script>
</x-guest-layout>
