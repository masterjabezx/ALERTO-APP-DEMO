<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#F5F7FA]">
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
<body class="h-full flex flex-col text-slate-800 antialiased selection:bg-[#3187D8] selection:text-white" style="font-family: 'Plus Jakarta Sans', sans-serif;">

    {{-- PWA Installation and Connection Status --}}
    <x-pwa-install-banner />
    <x-pwa-connection-indicator />

    {{-- Office Header --}}
    <header class="sticky top-0 z-30 bg-white border-b border-slate-200/90 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3.5">
                <img src="/images/branding/alerto-logo-web-128.png" alt="ALERTO APP" class="w-10 h-10 rounded-xl object-contain flex-shrink-0">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-extrabold text-[#173957] tracking-tight text-base">ALERTO</span>
                        @php
                            $officeUser = auth()->user()->officeUsers->first() ?? null;
                            $officeLevel = $officeUser?->office_level ?? 'office_staff';
                        @endphp
                        <x-badge :variant="$officeLevel === 'office_admin' ? 'warning' : 'info'" size="sm">
                            {{ $officeLevel === 'office_admin' ? 'Office Admin' : 'Office Staff' }}
                        </x-badge>
                    </div>
                    <p class="text-[10px] text-slate-500 font-medium">Hinoba-an Emergency Operations Desk</p>
                </div>
            </div>

            {{-- Office 5 Main Navigation Items --}}
            <nav class="hidden lg:flex items-center gap-1 bg-slate-100 p-1.5 rounded-2xl border border-slate-200">
                <a href="{{ route('office.dashboard') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('office.dashboard') ? 'bg-white text-[#173957] shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    1. Office Dashboard
                </a>
                <a href="{{ route('office.incidents') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('office.incidents*') ? 'bg-white text-[#173957] shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    2. Incident Monitoring
                </a>
                <a href="{{ route('office.map') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('office.map') ? 'bg-white text-[#173957] shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    3. Office Incident Map
                </a>
                <a href="{{ route('office.records') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('office.records*') ? 'bg-white text-[#173957] shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    4. Records &amp; Reports
                </a>
                <a href="{{ route('office.messages') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('office.messages') ? 'bg-white text-[#173957] shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    5. Messages
                </a>
                @if(auth()->user()->canManageOfficeStaffAccounts())
                    <a href="{{ route('office.personnel') }}"
                       class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('office.personnel*') ? 'bg-white text-[#173957] shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        Unit Roster
                    </a>
                @endif
            </nav>

            {{-- User Menu --}}
            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block">
                    <p class="text-xs font-bold text-slate-800">{{ auth()->user()->name ?? 'Office User' }}</p>
                    <p class="text-[10px] text-slate-500">{{ auth()->user()->email ?? '' }}</p>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="p-2.5 rounded-xl text-slate-400 hover:text-[#DB4545] hover:bg-slate-100 transition-colors" title="Sign Out">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                    </button>
                </form>
            </div>
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
