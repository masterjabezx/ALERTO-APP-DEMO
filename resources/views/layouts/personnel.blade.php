<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-default-theme="light" class="h-full alerto-shell theme-light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Personnel Field Operations — ALERTO Hinoba-an' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <x-pwa-meta />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex flex-col bg-[var(--surface-bg)] text-[var(--text-primary)] antialiased selection:bg-[#E87524] selection:text-white pb-20 md:pb-0 transition-colors duration-200" style="font-family: 'Plus Jakarta Sans', sans-serif;">

    {{-- PWA Installation and Connection Status --}}
    <x-pwa-install-banner />
    <x-pwa-connection-indicator />

    {{-- Personnel Header --}}
    <header class="sticky top-0 z-30 bg-[#17191C] text-[#F5F3EF] border-b border-white/10 shadow-md">
        <div class="h-1 bg-gradient-to-r from-[#E87524] via-[#F29A56] to-[#E87524]"></div>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-3">
            <a href="{{ route('personnel.dashboard') }}" class="flex items-center gap-3 min-w-0">
                <img src="/images/branding/alerto-logo-web-128.png" alt="ALERTO APP" class="w-10 h-10 rounded-xl object-contain shrink-0">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-black tracking-tight text-base text-white">ALERTO</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#F29A56] bg-[#E87524]/15 px-1.5 py-0.5 rounded-sm border border-[#E87524]/30">Field Operative</span>
                    </div>
                    <p class="text-[10px] text-[#A39E98] font-medium truncate max-w-[190px] sm:max-w-xs">Hinoba-an Response Unit</p>
                </div>
            </a>

            {{-- Theme Toggle, Availability State Toggle & User Menu --}}
            <div class="flex items-center gap-2.5 shrink-0">
                <x-theme-toggle compact />

                @php
                    $avail = auth()->user()->personnelAvailability ?? null;
                    $isOnline = $avail && in_array($avail->operational_status, ['available', 'online', 'dispatched', 'on_scene']);
                @endphp

                <a href="{{ route('personnel.availability') }}" 
                   class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold transition-all {{ $isOnline ? 'bg-[#27845B]/20 text-[#5BD19A] border border-[#27845B]/45 hover:bg-[#27845B]/30' : 'bg-[#292D32] text-[#D1CEC9] border border-white/15 hover:border-[#E87524]/50' }}"
                   title="Click to toggle availability">
                    <span class="relative flex h-2 w-2">
                        @if($isOnline)
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#5BD19A] opacity-75"></span>
                        @endif
                        <span class="relative inline-flex rounded-full h-2 w-2 {{ $isOnline ? 'bg-[#5BD19A]' : 'bg-[#A39E98]' }}"></span>
                    </span>
                    <span class="hidden sm:inline">{{ $isOnline ? 'ONLINE' : 'OFFLINE' }}</span>
                </a>

                {{-- Logout --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="p-2 rounded-xl text-[#D1CEC9] hover:text-[#F27D80] hover:bg-white/10 transition-colors cursor-pointer" title="Sign Out">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                    </button>
                </form>
            </div>
        </div>

        {{-- Desktop Navigation (5 Main Items) --}}
        <div class="hidden md:block bg-[#101214] border-t border-white/10">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 flex items-center gap-1 py-1.5">
                <a href="{{ route('personnel.dashboard') }}" 
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-colors {{ (request()->routeIs('personnel.dashboard') || request()->routeIs('personnel.tasks')) ? 'bg-[#E87524] text-white shadow-xs shadow-[#E87524]/30' : 'text-[#D1CEC9] hover:text-white hover:bg-white/5' }}">
                    1. Tasks
                </a>
                <a href="{{ route('personnel.active-incidents') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-colors {{ request()->routeIs('personnel.active-incidents') ? 'bg-[#E87524] text-white shadow-xs shadow-[#E87524]/30' : 'text-[#D1CEC9] hover:text-white hover:bg-white/5' }}">
                    2. Active Incidents
                </a>
                <a href="{{ route('personnel.map') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-colors {{ request()->routeIs('personnel.map') ? 'bg-[#E87524] text-white shadow-xs shadow-[#E87524]/30' : 'text-[#D1CEC9] hover:text-white hover:bg-white/5' }}">
                    3. Live Incident Map
                </a>
                <a href="{{ route('personnel.records') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-colors {{ request()->routeIs('personnel.records') ? 'bg-[#E87524] text-white shadow-xs shadow-[#E87524]/30' : 'text-[#D1CEC9] hover:text-white hover:bg-white/5' }}">
                    4. Records &amp; Incidents
                </a>
                <a href="{{ route('personnel.messages') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-colors {{ request()->routeIs('personnel.messages*') ? 'bg-[#E87524] text-white shadow-xs shadow-[#E87524]/30' : 'text-[#D1CEC9] hover:text-white hover:bg-white/5' }}">
                    5. Messages
                </a>
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 py-6">
        {{ $slot }}
    </main>

    {{-- Personnel Mobile Bottom Navigation (5 Items) --}}
    <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-[var(--surface-card)]/95 backdrop-blur-md border-t border-[var(--border-subtle)] shadow-[0_-4px_20px_rgba(0,0,0,0.08)]">
        <div class="grid grid-cols-5 h-16 max-w-lg mx-auto">
            <a href="{{ route('personnel.dashboard') }}" class="flex flex-col items-center justify-center gap-1 font-bold text-[10px] {{ request()->routeIs('personnel.dashboard') ? 'text-[#E87524]' : 'text-[var(--text-muted)]' }}">
                <i data-lucide="check-square" class="w-4 h-4"></i>
                <span>Tasks</span>
            </a>
            <a href="{{ route('personnel.active-incidents') }}" class="flex flex-col items-center justify-center gap-1 font-semibold text-[10px] {{ request()->routeIs('personnel.active-incidents') ? 'text-[#E87524]' : 'text-[var(--text-muted)]' }}">
                <i data-lucide="flame" class="w-4 h-4 text-[#C83F43]"></i>
                <span>Active</span>
            </a>
            <a href="{{ route('personnel.map') }}" class="flex flex-col items-center justify-center gap-1 font-semibold text-[10px] {{ request()->routeIs('personnel.map') ? 'text-[#E87524]' : 'text-[var(--text-muted)]' }}">
                <i data-lucide="map" class="w-4 h-4"></i>
                <span>Map</span>
            </a>
            <a href="{{ route('personnel.records') }}" class="flex flex-col items-center justify-center gap-1 font-semibold text-[10px] {{ request()->routeIs('personnel.records') ? 'text-[#E87524]' : 'text-[var(--text-muted)]' }}">
                <i data-lucide="folder-archive" class="w-4 h-4"></i>
                <span>Records</span>
            </a>
            <a href="{{ route('personnel.messages') }}" class="flex flex-col items-center justify-center gap-1 font-semibold text-[10px] {{ request()->routeIs('personnel.messages*') ? 'text-[#E87524]' : 'text-[var(--text-muted)]' }}">
                <i data-lucide="message-square" class="w-4 h-4"></i>
                <span>Chat</span>
            </a>
        </div>
    </nav>

    <x-toast />
    @stack('scripts')
</body>
</html>
