<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-default-theme="light" class="h-full alerto-shell theme-light">
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
<body class="h-full flex flex-col bg-[var(--surface-bg)] text-[var(--text-primary)] antialiased selection:bg-[#E87524] selection:text-white transition-colors duration-200" style="font-family: 'Plus Jakarta Sans', sans-serif;" x-data="{ mobileNavOpen: false }">

    {{-- PWA Installation and Connection Status --}}
    <x-pwa-install-banner />
    <x-pwa-connection-indicator />

    {{-- Office Header (Carbon Accent + Warm Ivory / Dark Responsive Surface) --}}
    <header class="sticky top-0 z-30 bg-[var(--surface-card)]/95 backdrop-blur-md border-b border-[var(--border-subtle)] shadow-xs">
        <x-demo-environment-banner />
        <div class="h-1 bg-gradient-to-r from-[#17191C] via-[#E87524] to-[#17191C]"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button"
                        @click="mobileNavOpen = !mobileNavOpen"
                        class="lg:hidden p-2 rounded-xl bg-[var(--surface-panel)] border border-[var(--border-subtle)] text-[var(--text-primary)] hover:border-[#E87524]/60 transition-colors cursor-pointer"
                        aria-label="Toggle Office Navigation">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <a href="{{ route('office.dashboard') }}" class="flex items-center gap-3 min-w-0">
                    <img src="/images/branding/alerto-logo-web-128.png" alt="ALERTO APP" class="w-10 h-10 rounded-xl object-contain shrink-0">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-black text-[var(--text-primary)] tracking-tight text-base">ALERTO</span>
                            @php
                                $officeUser = auth()->user()->officeUsers->first() ?? null;
                                $officeLevel = $officeUser?->office_level ?? 'office_staff';
                            @endphp
                            <x-badge :variant="$officeLevel === 'office_admin' ? 'warning' : 'info'" size="sm">
                                {{ $officeLevel === 'office_admin' ? 'Office Admin' : 'Office Staff' }}
                            </x-badge>
                        </div>
                        <p class="text-[10px] text-[var(--text-muted)] font-semibold truncate">Hinoba-an Emergency Operations Desk</p>
                    </div>
                </a>
            </div>

            {{-- Office 5 Main Navigation Items --}}
            <nav class="hidden lg:flex items-center gap-1 bg-[var(--surface-panel)] p-1.5 rounded-2xl border border-[var(--border-subtle)]">
                <a href="{{ route('office.dashboard') }}" 
                   class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('office.dashboard') ? 'bg-[#E87524] text-white shadow-xs shadow-[#E87524]/30' : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)] hover:bg-[var(--surface-hover)]' }}">
                    1. Office Dashboard
                </a>
                <a href="{{ route('office.incidents') }}" 
                   class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('office.incidents*') ? 'bg-[#E87524] text-white shadow-xs shadow-[#E87524]/30' : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)] hover:bg-[var(--surface-hover)]' }}">
                    2. Incident Monitoring
                </a>
                <a href="{{ route('office.map') }}" 
                   class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('office.map') ? 'bg-[#E87524] text-white shadow-xs shadow-[#E87524]/30' : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)] hover:bg-[var(--surface-hover)]' }}">
                    3. Office Incident Map
                </a>
                <a href="{{ route('office.records') }}" 
                   class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('office.records*') ? 'bg-[#E87524] text-white shadow-xs shadow-[#E87524]/30' : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)] hover:bg-[var(--surface-hover)]' }}">
                    4. Records &amp; Reports
                </a>
                <a href="{{ route('office.messages') }}" 
                   class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('office.messages*') ? 'bg-[#E87524] text-white shadow-xs shadow-[#E87524]/30' : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)] hover:bg-[var(--surface-hover)]' }}">
                    5. Messages
                </a>
                @if(auth()->user()->canManageOfficeStaffAccounts())
                    <a href="{{ route('office.personnel') }}"
                       class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('office.personnel*') ? 'bg-[#E87524] text-white shadow-xs shadow-[#E87524]/30' : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)] hover:bg-[var(--surface-hover)]' }}">
                        Unit Roster
                    </a>
                @endif
            </nav>

            {{-- Theme Toggle & User Menu --}}
            <div class="flex items-center gap-2.5 shrink-0">
                <x-theme-toggle compact />

                <div class="text-right hidden sm:block">
                    <p class="text-xs font-bold text-[var(--text-primary)]">{{ auth()->user()->name ?? 'Office User' }}</p>
                    <p class="text-[10px] text-[var(--text-muted)]">{{ auth()->user()->email ?? '' }}</p>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="p-2.5 rounded-xl bg-[var(--surface-panel)] border border-[var(--border-subtle)] text-[var(--text-secondary)] hover:text-[#C83F43] hover:border-[#C83F43]/40 transition-colors cursor-pointer" title="Sign Out">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </div>

        {{-- Responsive Mobile Office Navigation Drawer --}}
        <div x-show="mobileNavOpen" x-transition x-cloak class="lg:hidden border-t border-[var(--border-subtle)] bg-[var(--surface-card)] px-4 py-3 space-y-1.5 shadow-lg">
            <a href="{{ route('office.dashboard') }}" class="block px-3.5 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('office.dashboard') ? 'bg-[#E87524] text-white' : 'text-[var(--text-secondary)] hover:bg-[var(--surface-panel)]' }}">
                1. Office Dashboard
            </a>
            <a href="{{ route('office.incidents') }}" class="block px-3.5 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('office.incidents*') ? 'bg-[#E87524] text-white' : 'text-[var(--text-secondary)] hover:bg-[var(--surface-panel)]' }}">
                2. Incident Monitoring
            </a>
            <a href="{{ route('office.map') }}" class="block px-3.5 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('office.map') ? 'bg-[#E87524] text-white' : 'text-[var(--text-secondary)] hover:bg-[var(--surface-panel)]' }}">
                3. Office Incident Map
            </a>
            <a href="{{ route('office.records') }}" class="block px-3.5 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('office.records*') ? 'bg-[#E87524] text-white' : 'text-[var(--text-secondary)] hover:bg-[var(--surface-panel)]' }}">
                4. Records &amp; Reports
            </a>
            <a href="{{ route('office.messages') }}" class="block px-3.5 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('office.messages*') ? 'bg-[#E87524] text-white' : 'text-[var(--text-secondary)] hover:bg-[var(--surface-panel)]' }}">
                5. Messages
            </a>
            @if(auth()->user()->canManageOfficeStaffAccounts())
                <a href="{{ route('office.personnel') }}" class="block px-3.5 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('office.personnel*') ? 'bg-[#E87524] text-white' : 'text-[var(--text-secondary)] hover:bg-[var(--surface-panel)]' }}">
                    Unit Roster
                </a>
            @endif
        </div>
    </header>

    {{-- Main Container --}}
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        {{ $slot }}
    </main>

    <x-toast />
    @stack('scripts')
</body>
</html>
