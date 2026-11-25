<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Office Portal — ALERTO Hinoba-an' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <x-pwa-meta />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body id="alerto-office-shell"
      data-default-theme="light"
      class="alerto-shell theme-light h-full flex flex-col antialiased selection:bg-[#E87524]/25"
      style="font-family: 'Plus Jakarta Sans', sans-serif;"
      x-data="{ mobileNavOpen: false }">

    <script>
        (function () {
            try {
                const saved = localStorage.getItem('alerto_portal_theme') || localStorage.getItem('alerto_admin_theme');
                const mode = saved === 'dark' ? 'dark' : 'light';
                const shell = document.getElementById('alerto-office-shell');
                if (shell) {
                    shell.classList.remove('theme-dark', 'theme-light');
                    shell.classList.add('theme-' + mode);
                }
                document.documentElement.classList.toggle('dark', mode === 'dark');
            } catch (e) {}
        })();
    </script>

    {{-- PWA Installation and Connection Status --}}
    <x-pwa-install-banner />
    <x-pwa-connection-indicator />

    {{-- Office Header --}}
    <header class="eoc-topbar sticky top-0 z-30 border-b shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3.5 min-w-0">
                <button type="button" @click="mobileNavOpen = !mobileNavOpen" class="p-2 rounded-xl hover:bg-black/5 dark:hover:bg-white/10 lg:hidden" aria-label="Toggle Office Menu">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <img src="/images/branding/alerto-logo-web-128.png" alt="ALERTO APP" class="w-10 h-10 rounded-xl object-contain shrink-0">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-extrabold tracking-tight text-base">ALERTO</span>
                        @php
                            $officeUser = auth()->user()->officeUsers->first() ?? null;
                            $officeLevel = $officeUser?->office_level ?? 'office_staff';
                        @endphp
                        <x-badge :variant="$officeLevel === 'office_admin' ? 'orange' : 'neutral'" size="sm">
                            {{ $officeLevel === 'office_admin' ? 'Office Admin' : 'Office Staff' }}
                        </x-badge>
                    </div>
                    <p class="text-[10px] text-[#77736E] font-medium truncate">Hinoba-an Emergency Operations Desk</p>
                </div>
            </div>

            {{-- Office 5 Main Navigation Items --}}
            <nav class="hidden lg:flex items-center gap-1 bg-[#F5F3EF] dark:bg-white/5 p-1.5 rounded-2xl border border-[#E6E3DE] dark:border-white/10">
                <a href="{{ route('office.dashboard') }}"
                   class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('office.dashboard') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:text-[#17191C] dark:hover:text-white' }}">
                    1. Office Dashboard
                </a>
                <a href="{{ route('office.incidents') }}"
                   class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('office.incidents*') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:text-[#17191C] dark:hover:text-white' }}">
                    2. Incident Monitoring
                </a>
                <a href="{{ route('office.map') }}"
                   class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('office.map') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:text-[#17191C] dark:hover:text-white' }}">
                    3. Office Incident Map
                </a>
                <a href="{{ route('office.records') }}"
                   class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('office.records*') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:text-[#17191C] dark:hover:text-white' }}">
                    4. Records &amp; Reports
                </a>
                <a href="{{ route('office.messages') }}"
                   class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('office.messages') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:text-[#17191C] dark:hover:text-white' }}">
                    5. Messages
                </a>
            </nav>

            {{-- User Menu --}}
            <div class="flex items-center gap-2 sm:gap-3">
                <x-theme-toggle />

                <div class="text-right hidden sm:block">
                    <p class="text-xs font-bold">{{ auth()->user()->name ?? 'Office User' }}</p>
                    <p class="text-[10px] text-[#77736E]">{{ auth()->user()->email ?? '' }}</p>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="p-2.5 rounded-xl text-[#77736E] hover:text-[#C83F43] hover:bg-black/5 dark:hover:bg-white/10 transition-colors" title="Sign Out">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                    </button>
                </form>
            </div>
        </div>

        {{-- Mobile Nav Drawer --}}
        <div x-show="mobileNavOpen" x-cloak class="lg:hidden border-t border-[#E6E3DE] dark:border-white/10 px-4 py-3 space-y-1.5 bg-white dark:bg-[#17191C]">
            <a href="{{ route('office.dashboard') }}" class="block px-3.5 py-2 rounded-xl text-xs font-bold {{ request()->routeIs('office.dashboard') ? 'bg-[#E87524] text-white' : '' }}">1. Office Dashboard</a>
            <a href="{{ route('office.incidents') }}" class="block px-3.5 py-2 rounded-xl text-xs font-bold {{ request()->routeIs('office.incidents*') ? 'bg-[#E87524] text-white' : '' }}">2. Incident Monitoring</a>
            <a href="{{ route('office.map') }}" class="block px-3.5 py-2 rounded-xl text-xs font-bold {{ request()->routeIs('office.map') ? 'bg-[#E87524] text-white' : '' }}">3. Office Incident Map</a>
            <a href="{{ route('office.records') }}" class="block px-3.5 py-2 rounded-xl text-xs font-bold {{ request()->routeIs('office.records*') ? 'bg-[#E87524] text-white' : '' }}">4. Records &amp; Reports</a>
            <a href="{{ route('office.messages') }}" class="block px-3.5 py-2 rounded-xl text-xs font-bold {{ request()->routeIs('office.messages') ? 'bg-[#E87524] text-white' : '' }}">5. Messages</a>
        </div>
    </header>

    {{-- Main Container --}}
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{ $slot }}
    </main>

    <x-toast />
    @stack('scripts')
</body>
</html>
