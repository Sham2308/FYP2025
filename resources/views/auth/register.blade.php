<x-guest-layout>
    {{-- Remove the Laravel logo --}}
    <x-slot name="logo"></x-slot>

    <style>
      :root{
        --bg:#0f172a;     /* base navy */
        --g1:#0c3f76;     /* deep blue 1  */
        --g2:#0b58a4;     /* deep blue 2  */
        --g3:#0a71b6;     /* blue/cyan hint */
      }

      /* ===== Smooth welcome-style gradient (no blobs) ===== */
      .bg-wrap{
        position:fixed; inset:0; z-index:0; overflow:hidden;
        background:
          radial-gradient(160vmax 120vmax at 50% 110%, rgba(0,0,0,.55) 10%, transparent 55%),
          radial-gradient(140vmax 100vmax at -20% -20%, rgba(0,0,0,.45) 10%, transparent 55%),
          linear-gradient(140deg, var(--g1) 0%, var(--g2) 42%, var(--g1) 70%, #08213d 100%);
      }
      .bg-sheen{
        position:absolute; inset:-10%;
        background:
          radial-gradient(80vmax 60vmax at 120% 0%, color-mix(in oklab, var(--g3) 18%, transparent) 0 35%, transparent 60%),
          radial-gradient(70vmax 50vmax at 0% 120%, color-mix(in oklab, var(--g2) 12%, transparent) 0 35%, transparent 60%);
        filter:saturate(110%);
        animation: drift 26s ease-in-out infinite alternate;
        opacity:.55;
        pointer-events:none;
      }
      @keyframes drift{
        0%   { transform: translate3d(-2%, -2%, 0) scale(1); }
        100% { transform: translate3d(2%, 2%, 0)  scale(1.03); }
      }
      .content-wrap{ position:relative; z-index:10; }

      /* ===== Scoped fix: readable text inside the white card ===== */
      .register-card input,
      .register-card select,
      .register-card textarea{
        color:#111827 !important;         /* slate-900 */
        caret-color:#111827;
        background:#ffffff;                /* ensure white bg */
        -webkit-text-fill-color:#111827;   /* Safari */
      }
      .register-card input::placeholder,
      .register-card textarea::placeholder{
        color:#9ca3af !important;          /* gray-400 */
        opacity:1;
      }
      .register-card input[disabled],
      .register-card input[readonly]{
        color:#6b7280 !important;          /* gray-500 */
        -webkit-text-fill-color:#6b7280;
      }
    </style>

    <!-- Background identical to welcome vibe -->
    <div class="bg-wrap">
      <div class="bg-sheen"></div>
    </div>

    <!-- Content -->
    <div class="content-wrap min-h-screen flex items-center justify-center p-4">
      <div class="w-full" style="max-width: 480px;">
        <div class="bg-white shadow-xl rounded-2xl p-8 register-card">
          <h1 class="text-4xl font-bold mb-8 text-center text-blue-600">Register User</h1>

          {{-- Flash banners --}}
          @if (session('success'))
            <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-green-800">
              {{ session('success') }}
            </div>
          @endif
          @if ($errors->any())
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-red-800">
              Please fix the errors below.
            </div>
          @endif

          <form method="POST" action="{{ route('register') }}" class="space-y-6">
            @csrf

            {{-- UID --}}
            <div>
              <label for="uid" class="block text-base text-blue-600 mb-2">UID (Scanned)</label>
              <div class="flex">
                <input id="uid" name="uid" type="text" value="{{ old('uid') }}" required autocomplete="off"
                       class="flex-1 h-11 border border-gray-300 px-3 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="button" id="scanBtn"
                        class="h-11 px-4 rounded-r-md bg-blue-600 text-white font-medium hover:bg-blue-700">
                  Scan Card
                </button>
              </div>
              @error('uid') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Name --}}
            <div>
              <label for="name" class="block text-base text-blue-600 mb-2">Name</label>
              <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name"
                     class="w-full h-11 border border-gray-300 px-3 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              @error('name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Email (used for Outlook notification) --}}
            <div>
              <label for="email" class="block text-base text-blue-600 mb-2">Email</label>
              <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"
                     placeholder="name@pb.edu.bn"
                     class="w-full h-11 border border-gray-300 px-3 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              @error('email') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Staff / Student ID --}}
            <div>
              <label for="staff_id" class="block text-base text-blue-600 mb-2">Student / Staff ID</label>
              <input id="staff_id" name="staff_id" type="text" value="{{ old('staff_id') }}" autocomplete="off"
                     class="w-full h-11 border border-gray-300 px-3 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              @error('staff_id') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Save / Cancel --}}
            <div class="flex gap-3 pt-2">
              <button type="submit" class="h-11 px-6 rounded-md bg-green-600 text-white font-medium hover:bg-green-700">
                Save
              </button>
              <a href="{{ url('/') }}" class="h-11 px-6 rounded-md bg-gray-600 text-white font-medium hover:bg-gray-700 flex items-center justify-center">
                Cancel
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script>
      // demo NFC
      document.getElementById('scanBtn')?.addEventListener('click', () => {
        const el = document.getElementById('uid');
        if (el && !el.value) el.value = 'FAKE-UID-123456';
      });
    </script>

    <script>
      // favicon
      (function () {
        const href = "{{ asset('images/main2-logo.png') }}";
        function ensureFavicon(rel) {
          let link = document.querySelector(`link[rel="${rel}"]`);
          if (!link) { link = document.createElement('link'); link.setAttribute('rel', rel); document.head.appendChild(link); }
          link.type = 'image/png'; link.href = href;
        }
        ensureFavicon('icon'); ensureFavicon('shortcut icon');
      })();
    </script>
</x-guest-layout>
