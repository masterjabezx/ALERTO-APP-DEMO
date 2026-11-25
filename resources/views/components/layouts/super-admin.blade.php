<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'LDRRMO SUPER ADMIN — ALERTO APP' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <x-pwa-meta />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $telemetry = $shellTelemetry ?? [
        'pending_incidents' => 0,
        'incoming_reports_count' => 0,
        'active_sos_count' => 0,
        'critical_active' => 0,
        'fb_unread_total' => 0,
        'internal_unread' => 0,
        'unread_notifications' => 0,
        'notifications' => collect(),
    ];
@endphp
<body class="min-h-full bg-[#070E1A] text-[#EDF4FC] flex antialiased" style="font-family: 'Plus Jakarta Sans', sans-serif;" x-data="{ mobileOpen: false }">

    {{-- Super Admin Navigation Sidebar (17 Required Items) --}}
    <aside class="w-72 shrink-0 bg-[#0B1726] border-r border-purple-500/20 hidden lg:flex flex-col">
        <div class="h-16 px-4 flex items-center gap-3 border-b border-white/10">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-purple-600 to-indigo-700 text-white flex items-center justify-center font-black shadow-lg">
                SA
            </div>
            <div class="truncate">
                <span class="font-extrabold text-sm text-white block tracking-wide">LDRRMO SUPER ADMIN</span>
                <span class="text-[10px] font-mono text-purple-300 uppercase">Highest System Authority</span>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto alerto-scrollbar p-3 space-y-1 text-xs">
            @php
                $navGroups = [
                    'EXECUTIVE OVERSIGHT' => [
                        ['label' => '1. Executive Dashboard', 'route' => 'super-admin.dashboard', 'match' => 'super-admin.dashboard', 'badge' => null],
                        ['label' => '2. System Monitoring', 'route' => 'super-admin.monitoring', 'match' => 'super-admin.monitoring', 'badge' => null],
                    ],
                    'EMERGENCY OPERATIONS' => [
                        ['label' => '3. Incoming Reports', 'route' => 'super-admin.incoming.index', 'match' => 'super-admin.incoming.*', 'badge' => $telemetry['incoming_reports_count'] ?? 0],
                        ['label' => '4. SOS Alerts Monitor', 'route' => 'super-admin.sos.index', 'match' => 'super-admin.sos.*', 'badge' => $telemetry['active_sos_count'] ?? 0],
                        ['label' => '5. All Official Incidents', 'route' => 'super-admin.incidents.index', 'match' => 'super-admin.incidents.*', 'badge' => $telemetry['pending_incidents'] ?? 0],
                        ['label' => '6. Operational Maps', 'route' => 'super-admin.map', 'match' => 'super-admin.map*', 'badge' => null],
                    ],
                    'ACCOUNTS GOVERNANCE' => [
                        ['label' => '7. Admin Accounts & Permissions', 'route' => 'super-admin.admins.index', 'match' => 'super-admin.admins.*', 'badge' => null],
                        ['label' => '8. Offices Management', 'route' => 'super-admin.offices.index', 'match' => 'super-admin.offices.*', 'badge' => null],
                        ['label' => '9. Personnel Management', 'route' => 'super-admin.personnel.index', 'match' => 'super-admin.personnel.*', 'badge' => null],
                        ['label' => '10. Citizen Accounts', 'route' => 'super-admin.citizens.index', 'match' => 'super-admin.citizens.*', 'badge' => null],
                    ],
                    'CONFIGURATION & DATA' => [
                        ['label' => '11. Automated Routing Rules', 'route' => 'super-admin.routing.index', 'match' => 'super-admin.routing.*', 'badge' => null],
                        ['label' => '12. Internal Communications', 'route' => 'super-admin.messages.index', 'match' => 'super-admin.messages.*', 'badge' => $telemetry['internal_unread'] ?? 0],
                        ['label' => '13. Reports & Analytics', 'route' => 'super-admin.reports.index', 'match' => 'super-admin.reports.*', 'badge' => null],
                        ['label' => '14. Command Center Hotlines', 'route' => 'super-admin.eoc-settings', 'match' => 'super-admin.eoc-settings*', 'badge' => null],
                        ['label' => '15. System & Categories Settings', 'route' => 'super-admin.settings.index', 'match' => 'super-admin.settings.*', 'badge' => null],
                        ['label' => '16. System Audit Logs', 'route' => 'super-admin.audit.index', 'match' => 'super-admin.audit.*', 'badge' => null],
                        ['label' => '17. Backup & Restore', 'route' => 'super-admin.backups.index', 'match' => 'super-admin.backups.*', 'badge' => null],
                    ],
                ];
            @endphp

            @foreach($navGroups as $group => $items)
                <div class="pt-2.5 first:pt-0">
                    <div class="px-3 pb-1 text-[9px] font-bold uppercase tracking-widest text-purple-300/60">{{ $group }}</div>
                    @foreach($items as $item)
                        @php
                            $active = request()->routeIs($item['match']);
                            $badge = (int) ($item['badge'] ?? 0);
                        @endphp
                        <a href="{{ route($item['route']) }}"
                           class="flex items-center justify-between gap-2 px-3 py-2 rounded-xl font-semibold transition-all {{ $active ? 'bg-purple-600 text-white shadow-md font-bold' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                            <span class="truncate">{{ $item['label'] }}</span>
                            @if($badge > 0)
                                <span class="px-1.5 py-0.5 rounded-full bg-rose-500 text-white text-[10px] font-mono font-bold">{{ $badge }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        <div class="p-3 border-t border-white/10 flex items-center justify-between gap-2 bg-[#101E2E]">
            <div class="truncate">
                <p class="text-xs font-bold text-white truncate">{{ auth()->user()->name }}</p>
                <a href="{{ route('admin.dashboard') }}" class="text-[10px] text-cyan-300 hover:underline">Open DIAL DAPH Desk &rarr;</a>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-white/10 hover:bg-rose-600 text-[11px] font-bold text-white cursor-pointer">
                    Logout
                </button>
            </form>
        </div>
    </aside>

    {{-- Workspace Column --}}
    <div class="flex-1 flex flex-col min-w-0">
        <header class="h-16 px-4 sm:px-8 border-b border-white/10 bg-[#0B1726]/90 backdrop-blur-md flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button type="button" @click="mobileOpen = !mobileOpen" class="lg:hidden px-3 py-1.5 rounded-lg bg-white/10 text-xs font-bold">Menu</button>
                <span class="px-3 py-1 rounded-full bg-purple-500/20 border border-purple-400/40 text-purple-200 text-xs font-bold">
                    LDRRMO SUPER ADMIN AUTHORITY
                </span>
            </div>
            <div class="flex items-center gap-3 text-xs">
                <a href="{{ route('super-admin.sos.index') }}" class="px-3 py-1.5 rounded-xl bg-rose-600/25 border border-rose-500/50 text-rose-200 font-bold">
                    Active SOS: {{ (int) ($telemetry['active_sos_count'] ?? 0) }}
                </a>
                <a href="{{ route('super-admin.incoming.index') }}" class="px-3 py-1.5 rounded-xl bg-amber-500/20 border border-amber-400/40 text-amber-200 font-bold">
                    Incoming: {{ (int) ($telemetry['incoming_reports_count'] ?? 0) }}
                </a>
            </div>
        </header>

        {{-- Mobile Nav Drawer --}}
        <div x-show="mobileOpen" x-cloak class="lg:hidden bg-[#0B1726] border-b border-white/10 p-4 grid grid-cols-2 gap-2 text-xs">
            @foreach($navGroups as $items)
                @foreach($items as $item)
                    <a href="{{ route($item['route']) }}" class="p-2 rounded-lg bg-white/5 text-slate-200 font-semibold truncate">{{ $item['label'] }}</a>
                @endforeach
            @endforeach
        </div>

        <main class="flex-1 p-4 sm:p-8 max-w-7xl w-full mx-auto">
            @if(session('success'))
                <div class="mb-4 p-4 rounded-2xl bg-emerald-500/20 border border-emerald-400/50 text-emerald-100 text-xs font-bold">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('warning'))
                <div class="mb-4 p-4 rounded-2xl bg-amber-500/20 border border-amber-400/50 text-amber-100 text-xs font-bold">
                    {{ session('warning') }}
                </div>
            @endif
            @if(session('error') || $errors->any())
                <div class="mb-4 p-4 rounded-2xl bg-rose-500/20 border border-rose-400/50 text-rose-100 text-xs font-bold">
                    @if(session('error')) <p>{{ session('error') }}</p> @endif
                    @foreach($errors->all() as $err) <p>&bull; {{ $err }}</p> @endforeach
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>

    <x-toast />
    @stack('scripts')
</body>
</html>

