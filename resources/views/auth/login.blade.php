<!-- resources/views/auth/login.blade.php -->
<x-guest-layout>

    <!-- Fix B: scoped CSS so input text is visible on white background -->
    <style>
      /* Scope to just this card */
      .auth-card input,
      .auth-card textarea,
      .auth-card select {
        color: #0f172a !important;                 /* dark text */
        -webkit-text-fill-color: #0f172a !important; /* Chrome/Safari */
      }
      .auth-card input::placeholder {
        color: #9ca3af !important;                 /* readable placeholder */
        opacity: 1;
      }
      /* Keep autofill readable and avoid yellow bg contrast issues */
      .auth-card input:-webkit-autofill,
      .auth-card textarea:-webkit-autofill,
      .auth-card select:-webkit-autofill {
        -webkit-text-fill-color: #0f172a !important;
        box-shadow: 0 0 0 1000px #ffffff inset !important;
      }
    </style>

    <div class="">
        <!-- CARD WRAPPER: tweak width & border here -->
        <div class="w-full max-w-[480px]">
            <div class="bg-white border-2 border-gray-300 rounded-xl shadow-lg p-6 auth-card">

                <!-- Logo -->
                <div class="text-center mb-6">
                    <img
                        src="{{ asset('images/main2-logo.png') }}"
                        alt="TapNBorrow"
                        class="mx-auto h-15 w-auto md:h-20 object-contain"
                    >
                </div>

                <!-- Session Status -->
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- Email Address -->
                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email"
                            class="block mt-1 w-full h-9 text-sm rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            type="email"
                            name="email"
                            :value="old('email')"
                            required autofocus
                            autocomplete="username" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2 text-xs" />
                    </div>

                    <!-- Password -->
                    <div class="mt-3">
                        <x-input-label for="password" :value="__('Password')" />
                        <x-text-input id="password"
                            class="block mt-1 w-full h-9 text-sm rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2 text-xs" />
                    </div>

                    <!-- Remember Me -->
                    <div class="block mt-3">
                        <label for="remember_me" class="inline-flex items-center">
                            <input id="remember_me" type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                name="remember">
                            <span class="ms-2 text-xs text-gray-600">{{ __('Remember me') }}</span>
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between mt-5">
                        @if (Route::has('password.request'))
                            <a class="text-xs text-blue-600 hover:underline"
                               href="{{ route('password.request') }}">
                                {{ __('Forgot your password?') }}
                            </a>
                        @endif

                        <x-primary-button class="px-4 py-1 text-sm">
                            {{ __('Log in') }}
                        </x-primary-button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-guest-layout>
