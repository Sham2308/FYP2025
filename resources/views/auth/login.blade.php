<!-- resources/views/auth/login.blade.php -->
<x-guest-layout>
    <div class="">
        <!-- CARD WRAPPER: tweak width & border here -->
        <div class="w-full max-w-[480px]">
            <div class="bg-white border-[3px] border-gray-300 rounded-2xl shadow-lg p-8">

                <!-- Logo / Title -->
                <div class="text-center mb-6">
                    <h1 class="text-4xl font-extrabold text-blue-600 tracking-wide">
                        TapNBorrow
                    </h1>
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
