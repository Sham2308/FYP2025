<nav x-data="{ open: false }" class="bg-blue-600 text-white">
    {{-- prevent Alpine dropdown flash --}}
    <style>[x-cloak]{display:none!important}</style>

    @php
        $homeUrl = auth()->check()
            ? (auth()->user()->role === 'admin'
                ? route('nfc.inventory')
                : (auth()->user()->role === 'technical'
                    ? route('technical.dashboard')
                    : route('borrow.index')))
            : route('welcome');
    @endphp

    <!-- Desktop -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-14">
            <!-- Brand -->
            <div class="flex items-center">
                <a href="{{ $homeUrl }}" class="font-bold text-xl tracking-tight">
                    TapNBorrow
                </a>
            </div>

            <!-- Links -->
            <div class="hidden sm:flex items-center space-x-2">
                @auth
                    {{-- Home --}}
                    <a href="{{ $homeUrl }}"
                       class="px-4 py-2 rounded-lg {{ url()->current() === $homeUrl ? 'bg-blue-700 font-semibold' : 'hover:bg-blue-500/30' }}">
                        Home
                    </a>

                    {{-- Inventory (admins) --}}
                    @if(auth()->user()->role === 'admin')
                        <a href="{{ route('nfc.inventory') }}"
                           class="px-4 py-2 rounded-lg {{ request()->routeIs('nfc.*') ? 'bg-blue-700 font-semibold' : 'hover:bg-blue-500/30' }}">
                            Inventory
                        </a>
                    @endif

                    {{-- Technical (technicals) --}}
                    @if(auth()->user()->role === 'technical')
                        <a href="{{ route('technical.dashboard') }}"
                           class="px-4 py-2 rounded-lg {{ request()->routeIs('technical.*') ? 'bg-blue-700 font-semibold' : 'hover:bg-blue-500/30' }}">
                            Technical
                        </a>
                    @endif

                    {{-- Borrow (everyone except technical) --}}
                    @if(auth()->user()->role !== 'technical')
                        <a href="{{ route('borrow.index') }}"
                           class="px-4 py-2 rounded-lg {{ request()->routeIs('borrow.*') ? 'bg-blue-700 font-semibold' : 'hover:bg-blue-500/30' }}">
                            Borrow
                        </a>
                    @endif

                    {{-- History (adjust the route name if yours is different) --}}
                    <a href="{{ route('history.index') }}"
                       class="px-4 py-2 rounded-lg {{ request()->routeIs('history.*') ? 'bg-blue-700 font-semibold' : 'hover:bg-blue-500/30' }}">
                        History
                    </a>

                    {{-- === Notifications === --}}
                    <div
                        x-data="{
                            open:false,
                            count: {{ auth()->user()->unreadNotifications()->count() }},
                            async refresh(){
                                try{
                                    const r = await fetch('{{ route('notifications.unreadCount') }}', { headers:{'X-Requested-With':'XMLHttpRequest'} });
                                    const d = await r.json(); this.count = d.count ?? 0;
                                }catch(_){}
                            }
                        }"
                        x-init="setInterval(()=>refresh(),30000)"
                        class="relative ml-1"
                    >
                        <button @click="open = !open"
                                class="relative p-2 rounded-full hover:bg-white/10 focus:outline-none"
                                aria-label="Notifications">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                 stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.311 6.022c1.78.68 3.6 1.134 5.454 1.31m5.714 0A3 3 0 019 20.25h6a3 3 0 01-1.286-3.168z" />
                            </svg>
                            <span x-show="count > 0" x-text="count"
                                  class="absolute -top-0.5 -right-0.5 text-[10px] leading-none px-1.5 py-0.5 bg-red-600 text-white rounded-full"></span>
                        </button>

                        <div x-cloak x-show="open" @click.outside="open = false"
                             class="absolute right-0 z-50 mt-2 w-80 bg-white text-gray-800 rounded-xl shadow-lg ring-1 ring-black/5 overflow-hidden">
                            <div class="flex items-center justify-between px-4 py-2 border-b">
                                <p class="font-semibold">Notifications</p>
                                <form method="POST" action="{{ route('notifications.markAllRead') }}">
                                    @csrf
                                    <button class="text-sm text-blue-600 hover:underline" type="submit"
                                            @click="setTimeout(()=>refresh(),400)">
                                        Mark all read
                                    </button>
                                </form>
                            </div>

                            @php $latest = auth()->user()->notifications()->latest()->take(10)->get(); @endphp
                            <ul class="max-h-80 overflow-auto divide-y">
                                @forelse($latest as $n)
                                    <li class="p-3 hover:bg-gray-50">
                                        <a href="{{ $n->data['url'] ?? '#' }}" class="block">
                                            <p class="text-sm font-medium {{ $n->read_at ? 'text-gray-500' : 'text-gray-900' }}">
                                                {{ $n->data['title'] ?? 'Notification' }}
                                            </p>
                                            @if(!empty($n->data['body']))
                                                <p class="text-xs text-gray-500 mt-1">{{ $n->data['body'] }}</p>
                                            @endif
                                            <span class="text-[10px] text-gray-400">{{ $n->created_at->diffForHumans() }}</span>
                                        </a>
                                        @if(is_null($n->read_at))
                                            <form method="POST" action="{{ route('notifications.markOneRead', $n->id) }}" class="mt-1">
                                                @csrf
                                                <button type="submit" class="text-xs text-gray-500 hover:text-gray-700"
                                                        @click="setTimeout(()=>refresh(),400)">Mark as read</button>
                                            </form>
                                        @endif
                                    </li>
                                @empty
                                    <li class="p-4 text-sm text-gray-500">No notifications yet.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                    {{-- === /Notifications === --}}

                    {{-- Logout (red) --}}
                    <form method="POST" action="{{ route('logout') }}" class="ml-2">
                        @csrf
                        <button type="submit"
                                class="px-4 py-2 rounded-lg bg-red-500 hover:bg-red-600 text-white">
                            Logout
                        </button>
                    </form>
                @endauth

                @guest
                    <a href="{{ route('borrow.index') }}" class="px-4 py-2 rounded-lg hover:bg-blue-500/30">Borrow</a>
                    <a href="{{ route('login') }}" class="px-4 py-2 rounded-lg hover:bg-blue-500/30">Login</a>
                @endguest
            </div>

            <!-- Hamburger -->
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

    <!-- Mobile -->
    <div x-show="open" x-transition class="sm:hidden border-t border-blue-500">
        <div class="px-4 pt-3 pb-4 space-y-1 bg-blue-600">
            @auth
                <a href="{{ $homeUrl }}"
                   class="block px-3 py-2 rounded {{ url()->current() === $homeUrl ? 'bg-blue-700 font-semibold' : 'hover:bg-blue-500/30' }}">
                    Home
                </a>

                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('nfc.inventory') }}"
                       class="block px-3 py-2 rounded {{ request()->routeIs('nfc.*') ? 'bg-blue-700 font-semibold' : 'hover:bg-blue-500/30' }}">
                        Inventory
                    </a>
                @endif

                @if(auth()->user()->role === 'technical')
                    <a href="{{ route('technical.dashboard') }}"
                       class="block px-3 py-2 rounded {{ request()->routeIs('technical.*') ? 'bg-blue-700 font-semibold' : 'hover:bg-blue-500/30' }}">
                        Technical
                    </a>
                @endif

                @if(auth()->user()->role !== 'technical')
                    <a href="{{ route('borrow.index') }}"
                       class="block px-3 py-2 rounded {{ request()->routeIs('borrow.*') ? 'bg-blue-700 font-semibold' : 'hover:bg-blue-500/30' }}">
                        Borrow
                    </a>
                @endif

                <a href="{{ route('history.index') }}"
                   class="block px-3 py-2 rounded {{ request()->routeIs('history.*') ? 'bg-blue-700 font-semibold' : 'hover:bg-blue-500/30' }}">
                    History
                </a>

                {{-- Simple notifications list --}}
                <div class="mt-2 rounded-lg bg-blue-700/40 p-2">
                    <div class="flex items-center justify-between px-1 py-1">
                        <span class="text-sm font-semibold">Notifications</span>
                        <form method="POST" action="{{ route('notifications.markAllRead') }}">
                            @csrf
                            <button class="text-xs underline" type="submit">Mark all read</button>
                        </form>
                    </div>
                    @php $latestMobile = auth()->user()->notifications()->latest()->take(5)->get(); @endphp
                    <ul class="divide-y divide-white/10">
                        @forelse($latestMobile as $n)
                            <li class="px-2 py-2">
                                <a href="{{ $n->data['url'] ?? '#' }}" class="block">
                                    <p class="text-sm {{ $n->read_at ? 'opacity-70' : 'font-medium' }}">
                                        {{ $n->data['title'] ?? 'Notification' }}
                                    </p>
                                    @if(!empty($n->data['body']))
                                        <p class="text-xs opacity-80">{{ $n->data['body'] }}</p>
                                    @endif
                                    <span class="text-[10px] opacity-70">{{ $n->created_at->diffForHumans() }}</span>
                                </a>
                            </li>
                        @empty
                            <li class="px-2 py-2 text-sm opacity-80">No notifications yet.</li>
                        @endforelse
                    </ul>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="px-1 pt-2">
                    @csrf
                    <button type="submit"
                            class="w-full text-left px-3 py-2 rounded bg-red-500 hover:bg-red-600 text-white">
                        Logout
                    </button>
                </form>
            @endauth

            @guest
                <a href="{{ route('borrow.index') }}" class="block px-3 py-2 rounded hover:bg-blue-500/30">Borrow</a>
                <a href="{{ route('login') }}" class="block px-3 py-2 rounded hover:bg-blue-500/30">Login</a>
            @endguest
        </div>
    </div>
</nav>

