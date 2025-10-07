<!-- resources/views/auth/login.blade.php -->
<x-guest-layout>
  <!-- favicon (unchanged) -->
  <script>
    (function () {
      var l = document.createElement('link');
      l.rel = 'icon';
      l.type = 'image/png';
      l.href = '{{ asset('images/main2.logo.png') }}';
      document.head.appendChild(l);
    })();
  </script>

  <style>
    :root{
      --bg:#0f172a;

      /* gradient blobs (pattern-free) */
      --g1:#1e3a8a;   /* deep blue */
      --g2:#0ea5e9;   /* cyan */
      --g3:#6366f1;   /* indigo */

      /* card tuning */
      --card-radius:12px;                   /* was 16px */
      --card-border:1px;                    /* thinner border */
      --card-border-color:rgba(255,255,255,.55);
    }

    /* background */
    .bg-wrap{position:fixed; inset:0; z-index:-1; overflow:hidden; background:var(--bg);}
    .bg-base{
      position:absolute; inset:-10%;
      background:
        radial-gradient(1100px 800px at 18% 20%, color-mix(in oklab, var(--g1) 38%, transparent) 0%, transparent 60%),
        radial-gradient(900px 700px at 82% 30%,  color-mix(in oklab, var(--g3) 32%, transparent) 0%, transparent 60%),
        radial-gradient(1200px 900px at 52% 85%, color-mix(in oklab, var(--g2) 28%, transparent) 0%, transparent 60%);
      filter: blur(2px);
    }

    /* modern glass card with slimmer border */
    .glass-card{
      background: rgba(255,255,255,.86);
      border: var(--card-border) solid var(--card-border-color);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-radius: var(--card-radius);
      box-shadow:
        0 18px 36px rgba(2,6,23,.32),
        inset 0 1px 0 rgba(255,255,255,.7);
      transition: box-shadow .25s ease, transform .25s ease, border-color .25s ease;
    }
    .glass-card:hover{
      transform: translateY(-2px);
      box-shadow:
        0 26px 54px rgba(2,6,23,.42),
        inset 0 1px 0 rgba(255,255,255,.8);
      border-color: rgba(255,255,255,.7);
    }

    /* inputs: match corner style + gentle focus glow */
    .modern-input{
      border-radius: 12px !important;  /* aligned with card */
      background-color: #ffffff !important;
      border-color: #e5e7eb !important;
      height: 42px;
      transition: box-shadow .2s ease, border-color .2s ease, transform .05s ease;
    }
    .modern-input:focus{
      border-color: #38bdf8 !important; /* sky-400 */
      box-shadow: 0 0 0 4px rgba(56,189,248,.25);
      outline: 0;
    }
    .modern-input:active{ transform: translateY(0.5px); }

    /* button: pill + subtle gradient + glow */
    .modern-btn{
      border-radius: 9999px !important;
      background-image: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%) !important;
      color:#fff !important;
      border: none !important;
      box-shadow: 0 10px 18px rgba(59,130,246,.28);
      transition: transform .12s ease, box-shadow .2s ease, filter .2s ease;
      padding: .55rem 1rem !important;
    }
    .modern-btn:hover{
      box-shadow: 0 14px 22px rgba(59,130,246,.36);
      filter: brightness(1.03);
      transform: translateY(-1px);
    }
    .modern-btn:active{ transform: translateY(0); }

    /* tiny logo hover lift */
    .logo-float{ transition: transform .25s ease; }
    .logo-float:hover{ transform: translateY(-2px) scale(1.01); }

    /* keep your original input visibility fixes */
    .auth-card input,
    .auth-card textarea,
    .auth-card select {
      color:#0f172a !important;
      -webkit-text-fill-color:#0f172a !important;
    }
    .auth-card input::placeholder { color:#9ca3af !important; opacity:1; }
    .auth-card input:-webkit-autofill,
    .auth-card textarea:-webkit-autofill,
    .auth-card select:-webkit-autofill {
      -webkit-text-fill-color:#0f172a !important;
      box-shadow:0 0 0 1000px #ffffff inset !important;
    }
  </style>

  <!-- background -->
  <div class="bg-wrap"><div class="bg-base"></div></div>

  <!-- centered layout -->
  <div class="min-h-[100svh] flex items-center justify-center p-4">
    <div class="w-full max-w-[480px]">
      <div class="glass-card p-6 md:p-7 auth-card">
        <div class="text-center mb-6">
          <img src="{{ asset('images/main3-logo.png') }}" alt="TapNBorrow"
               class="logo-float mx-auto h-16 md:h-20 w-auto object-contain">
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}">
          @csrf

          <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email"
              class="modern-input block mt-1 w-full text-sm"
              type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-xs" />
          </div>

          <div class="mt-3">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password"
              class="modern-input block mt-1 w-full text-sm"
              type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-xs" />
          </div>

          <div class="block mt-3">
            <label for="remember_me" class="inline-flex items-center select-none">
              <input id="remember_me" type="checkbox"
                     class="h-4 w-4 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                     name="remember">
              <span class="ms-2 text-xs text-gray-600">{{ __('Remember me') }}</span>
            </label>
          </div>

          <div class="flex items-center justify-between mt-5">
            @if (Route::has('password.request'))
              <a class="text-xs text-blue-600 hover:underline" href="{{ route('password.request') }}">
                {{ __('Forgot your password?') }}
              </a>
            @endif

            <x-primary-button class="modern-btn">
              {{ __('Log in') }}
            </x-primary-button>
          </div>
        </form>

      </div>
    </div>
  </div>
</x-guest-layout>
