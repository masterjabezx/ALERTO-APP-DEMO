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

    $navGroups = [
        'EXECUTIVE OVERSIGHT' => [
            ['label' => '1. Executive Dashboard', 'icon' => 'layout-dashboard', 'route' => 'super-admin.dashboard', 'match' => 'super-admin.dashboard', 'badge' => null],
            ['label' => '2. System Monitoring', 'icon' => 'activity', 'route' => 'super-admin.monitoring', 'match' => 'super-admin.monitoring', 'badge' => null],
        ],
        'EMERGENCY OPERATIONS' => [
            ['label' => '3. Incoming Reports', 'icon' => 'inbox', 'route' => 'super-admin.incoming.index', 'match' => 'super-admin.incoming.*', 'badge' => $telemetry['incoming_reports_count'] ?? 0, 'badgeVariant' => 'brand'],
            ['label' => '4. SOS Monitoring', 'icon' => 'siren', 'route' => 'super-admin.sos.index', 'match' => 'super-admin.sos.*', 'badge' => $telemetry['active_sos_count'] ?? 0, 'badgeVariant' => 'critical'],
            ['label' => '5. Incident Management', 'icon' => 'alert-triangle', 'route' => 'super-admin.incidents.index', 'match' => 'super-admin.incidents.*', 'badge' => $telemetry['pending_incidents'] ?? 0, 'badgeVariant' => 'brand'],
            ['label' => '6. Live Operations Map', 'icon' => 'map-pin', 'route' => 'super-admin.map', 'match' => 'super-admin.map*', 'badge' => null],
        ],
        'GOVERNANCE & FORCES' => [
            ['label' => '7. Admin Accounts', 'icon' => 'shield-check', 'route' => 'super-admin.admins.index', 'match' => 'super-admin.admins.*', 'badge' => null],
            ['label' => '8. Office Management', 'icon' => 'building-2', 'route' => 'super-admin.offices.index', 'match' => 'super-admin.offices.*', 'badge' => null],
            ['label' => '9. Personnel Management', 'icon' => 'users', 'route' => 'super-admin.personnel.index', 'match' => 'super-admin.personnel.*', 'badge' => null],
            ['label' => '10. Citizen Accounts', 'icon' => 'user-check', 'route' => 'super-admin.citizens.index', 'match' => 'super-admin.citizens.*', 'badge' => null],
            ['label' => '11. Routing & Dispatch', 'icon' => 'git-branch', 'route' => 'super-admin.routing.index', 'match' => 'super-admin.routing.*', 'badge' => null],
        ],
        'SYSTEM & ANALYTICS' => [
            ['label' => '12. Internal Messenger', 'icon' => 'messages-square', 'route' => 'super-admin.messages.index', 'match' => 'super-admin.messages.*', 'badge' => $telemetry['internal_unread'] ?? 0, 'badgeVariant' => 'brand'],
            ['label' => '13. Reports & Analytics', 'icon' => 'bar-chart-3', 'route' => 'super-admin.reports.index', 'match' => 'super-admin.reports.*', 'badge' => null],
            ['label' => '14. Command Center Settings', 'icon' => 'sliders', 'route' => 'super-admin.eoc-settings', 'match' => 'super-admin.eoc-settings*', 'badge' => null],
            ['label' => '15. System Settings', 'icon' => 'settings', 'route' => 'super-admin.settings.index', 'match' => 'super-admin.settings.*', 'badge' => null],
            ['label' => '16. Activity Logs', 'icon' => 'file-clock', 'route' => 'super-admin.audit.index', 'match' => 'super-admin.audit.*', 'badge' => null],
            ['label' => '17. Database Backups', 'icon' => 'database', 'route' => 'super-admin.backups.index', 'match' => 'super-admin.backups.*', 'badge' => null],
        ],
    ];
@endphp
<body
    id="eoc-super-admin-shell"
    data-default-theme="dark"
    class="alerto-shell theme-dark min-h-full flex antialiased selection:bg-[#E87524] selection:text-white"
    style="font-family: 'Plus Jakarta Sans', sans-serif;"
    x-data="{ mobileOpen: false, sidebarOpen: true }"
    x-init="try { if (localStorage.getItem('alerto_sa_sidebar') === '0') sidebarOpen = false; } catch (e) {}"
>
    <script>
        (function () {
            try {
                const saved = localStorage.getItem('alerto_portal_theme') || localStorage.getItem('alerto_admin_theme');
                const shell = document.getElementById('eoc-super-admin-shell');
                if (shell) {
                    const mode = saved === 'light' ? 'light' : 'dark';
                    shell.classList.remove('theme-dark', 'theme-light');
                    shell.classList.add('theme-' + mode);
                    document.documentElement.classList.toggle('dark', mode === 'dark');
                }
            } catch (e) {}
        })();
    </script>

    {{-- Skip to Content --}}
    <a href="#super-admin-main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[1200] focus:px-4 focus:py-2 focus:rounded-xl focus:bg-[#E87524] focus:text-white focus:font-bold focus:text-xs">
        Skip to executive workspace
    </a>

    {{-- Mobile Drawer Backdrop --}}
    <div x-show="mobileOpen" x-transition.opacity x-on:click="mobileOpen = false" class="fixed inset-0 z-40 bg-[#101214]/75 backdrop-blur-xs lg:hidden" x-cloak></div>

    {{-- Executive Carbon Black Sidebar --}}
    <aside
        id="eoc-sidebar"
        aria-label="LDRRMO Super Admin Navigation"
        class="fixed inset-y-0 left-0 z-50 bg-[#17191C] text-[#F5F3EF] border-r border-white/10 flex flex-col transition-all duration-300 ease-out shadow-2xl"
        :class="[
            sidebarOpen ? 'lg:w-72' : 'lg:w-20',
            mobileOpen ? 'translate-x-0 w-72' : '-translate-x-full lg:translate-x-0'
        ]"
    >
        <div class="h-16 px-4 flex items-center justify-between gap-2 border-b border-white/10 shrink-0 bg-[#101214]">
            <a href="{{ route('super-admin.dashboard') }}" class="flex items-center gap-3 overflow-hidden">
                <img src="/images/branding/alerto-logo-web-128.png" alt="ALERTO APP" class="w-10 h-10 rounded-xl object-contain shrink-0">
                <div x-show="sidebarOpen || mobileOpen" class="truncate">
                    <span class="font-extrabold text-sm text-white block tracking-wide">LDRRMO SUPER ADMIN</span>
                    <span class="text-[10px] font-mono text-[#F29A56] uppercase tracking-wider">Hinoba-an Governance</span>
                </div>
            </a>

            <button
                type="button"
                x-on:click="sidebarOpen = !sidebarOpen; try { localStorage.setItem('alerto_sa_sidebar', sidebarOpen ? '1' : '0'); } catch (e) {}"
                class="hidden lg:flex p-1.5 rounded-lg text-[#9D9993] hover:text-white hover:bg-white/10 transition-colors cursor-pointer"
                :aria-label="sidebarOpen ? 'Collapse Sidebar' : 'Expand Sidebar'"
                title="Toggle Sidebar"
            >
                <i data-lucide="panel-left-close" class="w-4 h-4 transition-transform" :class="!sidebarOpen ? 'rotate-180' : ''"></i>
            </button>

            <button
                type="button"
                x-on:click="mobileOpen = false"
                class="lg:hidden p-1.5 rounded-lg text-[#9D9993] hover:text-white hover:bg-white/10 cursor-pointer"
                aria-label="Close navigation menu"
            >
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto alerto-scrollbar p-3 space-y-2.5 text-xs">
            @foreach($navGroups as $group => $items)
                <div class="space-y-1">
                    <div x-show="sidebarOpen || mobileOpen" class="px-3 pb-1 text-[9px] font-bold uppercase tracking-[0.14em] text-[#9D9993]">
                        {{ $group }}
                    </div>
                    @foreach($items as $item)
                        @php
                            $active = request()->routeIs($item['match']);
                            $badge = (int) ($item['badge'] ?? 0);
                            $isCriticalBadge = ($item['badgeVariant'] ?? '') === 'critical';
                        @endphp
                        <a href="{{ route($item['route']) }}"
                           class="eoc-nav-link relative flex items-center justify-between gap-2.5 px-3 py-2 rounded-xl font-semibold transition-all group {{ $active ? 'bg-[#E87524] text-white shadow-md shadow-[#E87524]/25 font-bold btn-fixed' : 'text-[#D4D0C9] hover:bg-white/5 hover:text-white' }}"
                           :title="(!sidebarOpen && !mobileOpen) ? '{{ $item['label'] }}' : ''">
                            @if($active)
                                <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-white"></span>
                            @endif
                            <div class="flex items-center gap-2.5 min-w-0">
                                <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 shrink-0 {{ $active ? 'text-white' : 'text-[#9D9993] group-hover:text-[#F29A56]' }}"></i>
                                <span x-show="sidebarOpen || mobileOpen" class="truncate">{{ $item['label'] }}</span>
                            </div>
                            @if($badge > 0)
                                <span x-show="sidebarOpen || mobileOpen"
                                      class="px-1.5 py-0.5 rounded-full text-[9px] font-mono font-black shrink-0 {{ $isCriticalBadge ? 'bg-[#C83F43] text-white' : ($active ? 'bg-black/25 text-white' : 'bg-[#E87524]/20 text-[#F29A56]') }}">
                                    {{ $badge > 99 ? '99+' : $badge }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        <div class="p-3 border-t border-white/10 flex items-center justify-between gap-2 bg-[#101214] shrink-0">
            <div x-show="sidebarOpen || mobileOpen" class="min-w-0">
                <p class="text-xs font-bold text-white truncate">{{ auth()->user()->name }}</p>
                <a href="{{ route('admin.dashboard') }}" class="text-[10px] font-semibold text-[#F29A56] hover:underline">Open DIAL DAPH Desk &rarr;</a>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-[#292D32] hover:bg-[#C83F43] text-[11px] font-bold text-white transition-colors cursor-pointer" title="Sign Out">
                    Logout
                </button>
            </form>
        </div>
    </aside>

    {{-- Workspace Column --}}
    <div class="flex-1 flex flex-col min-w-0 transition-all duration-300 alerto-grid-canvas"
         :class="sidebarOpen ? 'lg:pl-72' : 'lg:pl-20'">
        <header class="alerto-topbar sticky top-0 z-30">
            <x-demo-environment-banner />
            <div class="h-16 px-4 sm:px-8 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" @click="mobileOpen = true" aria-label="Open navigation menu" class="lg:hidden p-2 rounded-xl border border-white/15 hover:border-[#E87524] transition-colors cursor-pointer">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <img src="/images/branding/alerto-logo-web-128.png" alt="ALERTO APP" class="w-8 h-8 rounded-lg object-contain lg:hidden shrink-0">
                <span class="hidden sm:inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#E87524]/15 border border-[#E87524]/40 text-[#E87524] text-[11px] font-mono font-bold uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-[#E87524]"></span>
                    LDRRMO SUPER ADMIN AUTHORITY
                </span>
            </div>

            <div class="flex items-center gap-2.5 text-xs">
                <a href="{{ route('super-admin.sos.index') }}" class="px-3 py-1.5 rounded-xl bg-[#C83F43] hover:bg-[#a93034] text-white font-bold shadow-xs transition-colors btn-fixed">
                    Active SOS: {{ (int) ($telemetry['active_sos_count'] ?? 0) }}
                </a>
                <a href="{{ route('super-admin.incoming.index') }}" class="hidden sm:inline-flex px-3 py-1.5 rounded-xl bg-[#E87524]/15 border border-[#E87524]/40 text-[#E87524] font-bold hover:bg-[#E87524]/25 transition-colors">
                    Incoming: {{ (int) ($telemetry['incoming_reports_count'] ?? 0) }}
                </a>
                <x-theme-toggle />
            </div>
            </div>
        </header>

        <main id="super-admin-main" class="flex-1 p-4 sm:p-6 lg:p-8 max-w-[1520px] w-full mx-auto">
            @if(session('success'))
                <div class="mb-5 p-4 rounded-2xl bg-[#27845B]/15 border border-[#27845B]/40 text-[#27845B] dark:text-[#6AE0B0] text-xs font-bold flex items-center gap-2.5">
                    <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('warning'))
                <div class="mb-5 p-4 rounded-2xl bg-[#D99024]/15 border border-[#D99024]/45 text-[#9a630d] dark:text-[#F7C768] text-xs font-bold flex items-center gap-2.5">
                    <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0"></i>
                    <span>{{ session('warning') }}</span>
                </div>
            @endif
            @if(session('error') || $errors->any())
                <div class="mb-5 p-4 rounded-2xl bg-[#C83F43]/15 border border-[#C83F43]/45 text-[#C83F43] dark:text-[#FDA4AF] text-xs font-bold space-y-1">
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
