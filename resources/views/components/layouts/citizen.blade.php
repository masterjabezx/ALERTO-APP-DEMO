<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-default-theme="light" class="h-full alerto-shell theme-light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Citizen Portal — ALERTO Hinoba-an' }}</title>

    {{-- Fonts --}}
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

    {{-- Citizen Top Header --}}
    <header class="sticky top-0 z-30 bg-[var(--surface-card)]/95 border-b border-[var(--border-subtle)] backdrop-blur-md shadow-xs">
        <x-demo-environment-banner />
        <div class="h-1 bg-gradient-to-r from-[#17191C] via-[#E87524] to-[#17191C]"></div>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-3">
            <a href="{{ route('citizen.dashboard') }}" class="flex items-center gap-3 min-w-0">
                <img src="/images/branding/alerto-logo-web-128.png" alt="ALERTO APP" class="w-10 h-10 rounded-xl object-contain shrink-0">
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5">
                        <span class="font-black text-[var(--text-primary)] tracking-tight text-base">ALERTO</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-white bg-[#27845B] px-1.5 py-0.5 rounded-sm">Citizen</span>
                    </div>
                    <p class="text-[10px] text-[var(--text-muted)] font-semibold truncate max-w-[190px] sm:max-w-xs">Hinoba-an LDRRMO</p>
                </div>
            </a>

            {{-- Desktop Navigation (Only 3 Items) --}}
            <nav class="hidden md:flex items-center gap-1 bg-[var(--surface-panel)] p-1 rounded-2xl border border-[var(--border-subtle)]">
                <a href="{{ route('citizen.dashboard') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('citizen.dashboard') || request()->routeIs('citizen.incident.*') ? 'bg-[#E87524] text-white shadow-xs shadow-[#E87524]/25' : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)] hover:bg-[var(--surface-hover)]' }}">
                   1. Submit Incident
                </a>
                <a href="{{ route('citizen.records') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('citizen.records*') ? 'bg-[#E87524] text-white shadow-xs shadow-[#E87524]/25' : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)] hover:bg-[var(--surface-hover)]' }}">
                   2. Incident Record
                </a>
                <a href="{{ route('citizen.contacts') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('citizen.contacts') ? 'bg-[#E87524] text-white shadow-xs shadow-[#E87524]/25' : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)] hover:bg-[var(--surface-hover)]' }}">
                   3. Contact Numbers
                </a>
            </nav>

            {{-- Theme Toggle & User Menu --}}
            <div class="flex items-center gap-2.5 shrink-0">
                <x-theme-toggle compact />

                <span class="hidden sm:inline-block text-xs font-bold text-[var(--text-primary)] truncate max-w-[130px]">
                    {{ auth()->user()->name ?? 'Citizen' }}
                </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" 
                            class="p-2 rounded-xl text-[var(--text-secondary)] hover:text-[#C83F43] hover:bg-[#C83F43]/10 transition-colors cursor-pointer"
                            title="Sign Out">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="flex-1 max-w-4xl w-full mx-auto px-4 sm:px-6 py-6">
        {{ $slot }}
    </main>

    {{-- Citizen Mobile Bottom Navigation (Only 3 Items) --}}
    <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-[var(--surface-card)]/95 border-t border-[var(--border-subtle)] backdrop-blur-md shadow-[0_-4px_20px_rgba(0,0,0,0.08)]">
        <div class="grid grid-cols-3 h-16 max-w-md mx-auto">
            <a href="{{ route('citizen.dashboard') }}" 
               class="flex flex-col items-center justify-center gap-1 font-bold text-[11px] active:scale-95 transition-transform {{ request()->routeIs('citizen.dashboard') || request()->routeIs('citizen.incident.*') ? 'text-[#E87524]' : 'text-[var(--text-muted)]' }}">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
                <span>Submit</span>
            </a>

            <a href="{{ route('citizen.records') }}" 
               class="flex flex-col items-center justify-center gap-1 font-semibold text-[11px] active:scale-95 transition-transform {{ request()->routeIs('citizen.records*') ? 'text-[#E87524] font-bold' : 'text-[var(--text-muted)]' }}">
                <i data-lucide="clock" class="w-5 h-5"></i>
                <span>Records</span>
            </a>

            <a href="{{ route('citizen.contacts') }}" 
               class="flex flex-col items-center justify-center gap-1 font-semibold text-[11px] active:scale-95 transition-transform {{ request()->routeIs('citizen.contacts') ? 'text-[#E87524] font-bold' : 'text-[var(--text-muted)]' }}">
                <i data-lucide="phone-call" class="w-5 h-5"></i>
                <span>Contacts</span>
            </a>
        </div>
    </nav>

    {{-- Toast container --}}
    <x-toast />

    @stack('scripts')
</body>
</html>
