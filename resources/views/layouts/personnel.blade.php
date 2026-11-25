<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#F5F7FA]">
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
<body class="h-full flex flex-col text-slate-800 antialiased selection:bg-[#3187D8] selection:text-white pb-20 md:pb-0" style="font-family: 'Plus Jakarta Sans', sans-serif;">

    {{-- PWA Installation and Connection Status --}}
    <x-pwa-install-banner />
    <x-pwa-connection-indicator />

    {{-- Personnel Header --}}
    <header class="sticky top-0 z-30 bg-[#173957] text-white border-b border-slate-700/60 shadow-md">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="/images/branding/alerto-logo-web-128.png" alt="ALERTO APP" class="w-10 h-10 rounded-xl object-contain flex-shrink-0">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-extrabold tracking-tight text-base">ALERTO</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#3187D8] bg-blue-950 px-1.5 py-0.5 rounded-sm border border-[#3187D8]/30">Field Operative</span>
                    </div>
                    <p class="text-[10px] text-slate-300 font-medium truncate max-w-[190px] sm:max-w-xs">Hinoba-an Response Unit</p>
                </div>
            </div>

            {{-- Availability State Toggle & User Menu --}}
            <div class="flex items-center gap-3">
                @php
                    $avail = auth()->user()->personnelAvailability ?? null;
                    $isOnline = $avail && in_array($avail->operational_status, ['available', 'online', 'dispatched', 'on_scene']);
                @endphp

                <a href="{{ route('personnel.availability') }}" 
                   class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold transition-all {{ $isOnline ? 'bg-[#19865B]/20 text-emerald-400 border border-[#19865B]/40 hover:bg-[#19865B]/30' : 'bg-slate-800 text-slate-400 border border-slate-700 hover:bg-slate-750' }}"
                   title="Click to toggle availability">
                    <span class="relative flex h-2 w-2">
                        @if($isOnline)
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        @endif
                        <span class="relative inline-flex rounded-full h-2 w-2 {{ $isOnline ? 'bg-emerald-400' : 'bg-slate-500' }}"></span>
                    </span>
                    <span class="hidden sm:inline">{{ $isOnline ? 'ONLINE' : 'OFFLINE' }}</span>
                </a>

                {{-- Logout --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="p-2 rounded-xl text-slate-300 hover:text-rose-400 hover:bg-white/10 transition-colors" title="Sign Out">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                    </button>
                </form>
            </div>
        </div>

        {{-- Desktop Navigation (5 Main Items) --}}
        <div class="hidden md:block bg-[#0f2438] border-t border-slate-800/80">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 flex items-center gap-1 py-1.5">
                <a href="{{ route('personnel.dashboard') }}" 
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-colors {{ (request()->routeIs('personnel.dashboard') || request()->routeIs('personnel.tasks')) ? 'bg-[#3187D8] text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                    1. Tasks
                </a>
                <a href="{{ route('personnel.active-incidents') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-colors {{ request()->routeIs('personnel.active-incidents') ? 'bg-[#3187D8] text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                    2. Active Incidents
                </a>
                <a href="{{ route('personnel.map') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-colors {{ request()->routeIs('personnel.map') ? 'bg-[#3187D8] text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                    3. Live Incident Map
                </a>
                <a href="{{ route('personnel.records') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-colors {{ request()->routeIs('personnel.records') ? 'bg-[#3187D8] text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                    4. Records &amp; Incidents
                </a>
                <a href="{{ route('personnel.messages') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-colors {{ request()->routeIs('personnel.messages*') ? 'bg-[#3187D8] text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
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
    <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white border-t border-slate-200 shadow-[0_-4px_20px_rgba(0,0,0,0.06)]">
        <div class="grid grid-cols-5 h-16 max-w-lg mx-auto">
            <a href="{{ route('personnel.dashboard') }}" class="flex flex-col items-center justify-center gap-1 font-bold text-[10px] {{ request()->routeIs('personnel.dashboard') ? 'text-[#3187D8]' : 'text-slate-500' }}">
                <i data-lucide="check-square" class="w-4 h-4"></i>
                <span>Tasks</span>
            </a>
            <a href="{{ route('personnel.active-incidents') }}" class="flex flex-col items-center justify-center gap-1 font-semibold text-[10px] {{ request()->routeIs('personnel.active-incidents') ? 'text-[#3187D8]' : 'text-slate-500' }}">
                <i data-lucide="flame" class="w-4 h-4 text-[#DB4545]"></i>
                <span>Active</span>
            </a>
            <a href="{{ route('personnel.map') }}" class="flex flex-col items-center justify-center gap-1 font-semibold text-[10px] {{ request()->routeIs('personnel.map') ? 'text-[#3187D8]' : 'text-slate-500' }}">
                <i data-lucide="map" class="w-4 h-4"></i>
                <span>Map</span>
            </a>
            <a href="{{ route('personnel.records') }}" class="flex flex-col items-center justify-center gap-1 font-semibold text-[10px] {{ request()->routeIs('personnel.records') ? 'text-[#3187D8]' : 'text-slate-500' }}">
                <i data-lucide="folder-archive" class="w-4 h-4"></i>
                <span>Records</span>
            </a>
            <a href="{{ route('personnel.messages') }}" class="flex flex-col items-center justify-center gap-1 font-semibold text-[10px] {{ request()->routeIs('personnel.messages*') ? 'text-[#3187D8]' : 'text-slate-500' }}">
                <i data-lucide="message-square" class="w-4 h-4"></i>
                <span>Chat</span>
            </a>
        </div>
    </nav>

    <x-toast />
    @stack('scripts')
</body>
</html>

