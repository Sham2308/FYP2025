<nav x-data="{ open: false }" class="bg-blue-600 text-white">
    <!-- Desktop / Primary Nav -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-14">
            <!-- Left: Brand -->
            <div class="flex items-center">
                <a
                    href="{{ auth()->check()
                        ? (auth()->user()->role === 'admin'
                            ? route('nfc.inventory')
                            : (auth()->user()->role === 'technical'
                                ? route('technical.dashboard')
                                : route('borrow.index')))
                        : route('welcome') }}"
                    class="font-bold text-xl tracking-tight"
                >
                    TapNBorrow
                </a>
            </div>

            <!-- Right: Links (desktop) -->
            <div class="hidden sm:flex items-center space-x-6">
                {{-- Logged-in users --}}
                @auth
                    @if(auth()->user()->role === 'admin')
                        <a href="{{ route('nfc.inventory') }}"
                           class="px-2 py-1 rounded hover:bg-blue-700 transition {{ request()->routeIs('nfc.*') ? 'bg-blue-700 font-semibold' : '' }}">
                            Inventory
                        </a>
                    @endif

                    @if(auth()->user()->role === 'technical')
                        <a href="{{ route('technical.dashboard') }}"
                           class="px-2 py-1 rounded hover:bg-blue-700 transition {{ request()->routeIs('technical.*') ? 'bg-blue-700 font-semibold' : '' }}">
                            Technical
                        </a>
                    @endif

                    {{-- Borrow only for non-technical roles --}}
                    @if(auth()->user()->role !== 'technical')
                        <a href="{{ route('borrow.index') }}"
                           class="px-2 py-1 rounded hover:bg-blue-700 transition {{ request()->routeIs('borrow.*') ? 'bg-blue-700 font-semibold' : '' }}">
                            Borrow
                        </a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}" class="ml-2">
                        @csrf
                        <button type="submit"
                                class="px-3 py-1 rounded border border-white/80 hover:bg-white hover:text-blue-700 transition text-sm">
                            Logout
                        </button>
                    </form>
                @endauth

                {{-- Guests --}}
                @guest
                    <a href="{{ route('borrow.index') }}"
                       class="px-2 py-1 rounded hover:bg-blue-700 transition {{ request()->routeIs('borrow.*') ? 'bg-blue-700 font-semibold' : '' }}">
                        Borrow
                    </a>

                    <a href="{{ route('login') }}"
                       class="px-3 py-1 rounded border border-white/80 hover:bg-white hover:text-blue-700 transition text-sm">
                        Login
                    </a>
                @endguest
            </div>

            <!-- Hamburger (mobile) -->
            <div class="sm:hidden">
                <button @click="open = !open"
                        class="p-2 rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-white/50"
                        aria-label="Toggle menu">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div x-show="open" x-transition class="sm:hidden border-t border-blue-500">
        <div class="px-4 pt-3 pb-4 space-y-1 bg-blue-600">
            {{-- Logged-in users --}}
            @auth
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('nfc.inventory') }}"
                       class="block px-3 py-2 rounded hover:bg-blue-700 {{ request()->routeIs('nfc.*') ? 'bg-blue-700 font-semibold' : '' }}">
                        Inventory
                    </a>
                @endif

                @if(auth()->user()->role === 'technical')
                    <a href="{{ route('technical.dashboard') }}"
                       class="block px-3 py-2 rounded hover:bg-blue-700 {{ request()->routeIs('technical.*') ? 'bg-blue-700 font-semibold' : '' }}">
                        Technical
                    </a>
                @endif

                {{-- Borrow only for non-technical roles --}}
                @if(auth()->user()->role !== 'technical')
                    <a href="{{ route('borrow.index') }}"
                       class="block px-3 py-2 rounded hover:bg-blue-700 {{ request()->routeIs('borrow.*') ? 'bg-blue-700 font-semibold' : '' }}">
                        Borrow
                    </a>
                @endif

                <form method="POST" action="{{ route('logout') }}" class="px-1 pt-2">
                    @csrf
                    <button type="submit"
                            class="w-full text-left px-3 py-2 rounded border border-white/80 hover:bg-white hover:text-blue-700 transition">
                        Logout
                    </button>
                </form>
            @endauth

            {{-- Guests --}}
            @guest
                <a href="{{ route('borrow.index') }}"
                   class="block px-3 py-2 rounded hover:bg-blue-700 {{ request()->routeIs('borrow.*') ? 'bg-blue-700 font-semibold' : '' }}">
                    Borrow
                </a>

                <a href="{{ route('login') }}"
                   class="block px-3 py-2 rounded hover:bg-blue-700">
                    Login
                </a>
            @endguest
        </div>
    </div>
</nav>
