<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ALERTO EOC — Hinoba-an Emergency Operations Center' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <x-pwa-meta />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $telemetry = $shellTelemetry ?? [
        'pending_incidents' => 0,
        'critical_active' => 0,
        'fb_unread_total' => 0,
        'internal_unread' => 0,
        'unread_notifications' => 0,
        'notifications' => collect(),
    ];
@endphp
<body
    id="eoc-admin-shell"
    class="eoc-shell theme-dark h-full flex antialiased selection:bg-[#3187D8] selection:text-white"
    style="font-family: 'Plus Jakarta Sans', sans-serif;"
    x-data="eocShellController({
        unreadNotifications: {{ (int) $telemetry['unread_notifications'] }},
        criticalCount: {{ (int) $telemetry['critical_active'] }},
        pendingIncidents: {{ (int) $telemetry['pending_incidents'] }}
    })"
    x-init="initShell()"
>
    {{-- Immediate pre-paint theme application to eliminate flash --}}
    <script>
        (function () {
            try {
                const saved = localStorage.getItem('alerto_admin_theme');
                const shell = document.getElementById('eoc-admin-shell');
                if (shell) {
                    shell.classList.remove('theme-dark', 'theme-light');
                    shell.classList.add(saved === 'light' ? 'theme-light' : 'theme-dark');
                }
            } catch (e) {}
        })();
    </script>

    {{-- Skip to Content Link for Keyboard Accessibility --}}
    <a href="#eoc-main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[1200] focus:px-4 focus:py-2 focus:rounded-xl focus:bg-[#34B9D4] focus:text-[#0B1726] focus:font-bold focus:text-xs">
        Skip to main command workspace
    </a>

    {{-- Mobile Backdrop --}}
    <div x-show="mobileMenuOpen"
         x-transition.opacity
         x-on:click="mobileMenuOpen = false"
         class="fixed inset-0 z-40 bg-[#0B1726]/75 backdrop-blur-xs lg:hidden"
         x-cloak></div>

    {{-- Admin Command Center Dark Navy Sidebar --}}
    <aside
        id="eoc-sidebar"
        aria-label="Admin Command Center Navigation"
        class="fixed inset-y-0 left-0 z-50 bg-[#0B1726] text-[#EDF4FC] flex flex-col transition-all duration-300 ease-out border-r border-slate-400/15 shadow-2xl"
        :class="[
            sidebarOpen ? 'lg:w-64' : 'lg:w-20',
            mobileMenuOpen ? 'translate-x-0 w-72' : '-translate-x-full lg:translate-x-0'
        ]"
    >
        {{-- Brand / Header --}}
        <div class="h-16 px-4 flex items-center justify-between border-b border-white/10 shrink-0 bg-[#101E2E]/70">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 overflow-hidden focus:outline-hidden">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-[#DB4545] to-[#b53030] text-white flex items-center justify-center shadow-lg shadow-[#DB4545]/30 shrink-0">
                    <i data-lucide="shield-alert" class="w-5 h-5"></i>
                </div>
                <div x-show="sidebarOpen || mobileMenuOpen" class="truncate">
                    <span class="font-extrabold text-[#EDF4FC] tracking-wider text-sm block">DIAL DAPH</span>
                    <span class="text-[10px] text-[#34B9D4] font-mono tracking-widest block">COMMAND CENTER</span>
                </div>
            </a>

            <button
                type="button"
                x-on:click="sidebarOpen = !sidebarOpen; localStorage.setItem('alerto_sidebar_open', sidebarOpen ? '1' : '0')"
                class="hidden lg:flex p-1.5 rounded-lg text-[#93A4B8] hover:text-white hover:bg-white/10 transition-colors"
                :aria-label="sidebarOpen ? 'Collapse Sidebar' : 'Expand Sidebar'"
                title="Toggle Sidebar"
            >
                <i data-lucide="panel-left-close" class="w-4 h-4 transition-transform" :class="!sidebarOpen ? 'rotate-180' : ''"></i>
            </button>

            <button
                type="button"
                x-on:click="mobileMenuOpen = false"
                class="lg:hidden p-1.5 rounded-lg text-[#93A4B8] hover:text-white hover:bg-white/10"
                aria-label="Close mobile menu"
            >
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        {{-- 14 DIAL DAPH Command Center Navigation Items --}}
        <nav class="flex-1 overflow-y-auto alerto-scrollbar p-3 space-y-1">
            @php
                $navGroups = [
                    'OPERATIONS' => [
                        ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'route' => 'admin.dashboard', 'badge' => null, 'badgeColor' => ''],
                        ['label' => 'Incoming Reports', 'icon' => 'inbox', 'route' => 'admin.incoming.index', 'match' => 'admin.incoming.*', 'badge' => $telemetry['incoming_reports_count'] ?? $telemetry['pending_incidents'], 'badgeColor' => 'bg-amber-500 text-slate-950'],
                        ['label' => 'SOS Alerts', 'icon' => 'siren', 'route' => 'admin.sos.index', 'match' => 'admin.sos.*', 'badge' => $telemetry['active_sos_count'] ?? 0, 'badgeColor' => 'bg-[#DB4545] text-white'],
                        ['label' => 'Incidents & Triage', 'icon' => 'alert-triangle', 'route' => 'admin.incidents.index', 'match' => 'admin.incidents.*', 'badge' => $telemetry['pending_incidents'], 'badgeColor' => 'bg-[#DB4545] text-white'],
                        ['label' => 'Live Operations Map', 'icon' => 'map-pin', 'route' => 'admin.map', 'match' => 'admin.map*', 'badge' => null, 'badgeColor' => ''],
                        ['label' => 'Dispatch Console', 'icon' => 'send', 'route' => 'admin.dispatch.index', 'match' => 'admin.dispatch.*', 'badge' => $telemetry['deployment_warnings_count'] ?? 0, 'badgeColor' => 'bg-amber-500 text-slate-950'],
                    ],
                    'FORCES & USERS' => [
                        ['label' => 'Office Management', 'icon' => 'building', 'route' => 'admin.offices.index', 'match' => 'admin.offices.*', 'badge' => null, 'badgeColor' => ''],
                        ['label' => 'Personnel Management', 'icon' => 'users-round', 'route' => 'admin.personnel.index', 'match' => 'admin.personnel.*', 'badge' => null, 'badgeColor' => ''],
                        ['label' => 'Citizen Accounts', 'icon' => 'user-check', 'route' => 'admin.citizens.index', 'match' => 'admin.citizens.*', 'badge' => null, 'badgeColor' => ''],
                        ['label' => 'Live User Monitor', 'icon' => 'activity', 'route' => 'admin.monitor.users', 'match' => 'admin.monitor.*', 'badge' => null, 'badgeColor' => ''],
                    ],
                    'COMMUNICATIONS' => [
                        ['label' => 'Internal Messenger', 'icon' => 'messages-square', 'route' => 'admin.messages.index', 'match' => 'admin.messages.*', 'badge' => $telemetry['internal_unread'], 'badgeColor' => 'bg-[#3187D8] text-white'],
                        ['label' => 'Facebook Messenger', 'icon' => 'message-circle', 'route' => 'admin.communications.fb.inbox', 'match' => 'admin.communications.fb.*', 'badge' => $telemetry['fb_unread_total'], 'badgeColor' => 'bg-[#34B9D4] text-[#0B1726]'],
                    ],
                    'GOVERNANCE' => [
                        ['label' => 'Reports & Analytics', 'icon' => 'bar-chart-3', 'route' => 'admin.reports.index', 'match' => 'admin.reports.*', 'badge' => null, 'badgeColor' => ''],
                        ['label' => 'Command Center Settings', 'icon' => 'sliders', 'route' => 'admin.eoc-settings', 'match' => 'admin.eoc-settings*', 'badge' => null, 'badgeColor' => ''],
                    ],
                ];
            @endphp

            @foreach($navGroups as $groupLabel => $items)
                <div class="pt-2.5 first:pt-0">
                    <div x-show="sidebarOpen || mobileMenuOpen" class="px-3 pb-1 text-[9px] font-bold uppercase tracking-[0.14em] text-[#93A4B8]/70">
                        {{ $groupLabel }}
                    </div>

                    @foreach($items as $item)
                        @php
                            $hasNamedRoute = \Illuminate\Support\Facades\Route::has($item['route']);
                            $matchPattern = $item['match'] ?? $item['route'];
                            $isActive = $hasNamedRoute && request()->routeIs($matchPattern);
                            $url = $hasNamedRoute ? route($item['route']) : '#';
                            $badgeVal = (int) ($item['badge'] ?? 0);
                        @endphp
                        <a
                            href="{{ $url }}"
                            class="eoc-nav-link relative flex items-center justify-between gap-2.5 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all group {{ $isActive ? 'bg-gradient-to-r from-[#3187D8] to-[#2770b6] text-white shadow-md shadow-[#3187D8]/25 font-bold' : 'text-[#93A4B8] hover:text-[#EDF4FC] hover:bg-white/5' }}"
                            :title="(!sidebarOpen && !mobileMenuOpen) ? '{{ $item['label'] }}' : ''"
                        >
                            @if($isActive)
                                <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-[#34B9D4]"></span>
                            @endif

                            <div class="flex items-center gap-3 min-w-0">
                                <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 shrink-0 {{ $isActive ? 'text-white' : 'text-[#93A4B8] group-hover:text-[#34B9D4]' }}"></i>
                                <span x-show="sidebarOpen || mobileMenuOpen" class="truncate">{{ $item['label'] }}</span>
                            </div>

                            @if($badgeVal > 0)
                                <span x-show="sidebarOpen || mobileMenuOpen"
                                      class="px-1.5 py-0.5 rounded-full text-[9px] font-mono font-black shrink-0 {{ $item['badgeColor'] }}">
                                    {{ $badgeVal > 99 ? '99+' : $badgeVal }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        {{-- Admin Sidebar Footer (Strictly Admin — Highest System Authority) --}}
        <div class="p-3 border-t border-white/10 shrink-0 bg-[#101E2E]/80">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2.5 overflow-hidden">
                    <div class="w-9 h-9 rounded-xl bg-[#DB4545]/20 text-[#DB4545] border border-[#DB4545]/40 flex items-center justify-center font-black text-xs shrink-0">
                        EOC
                    </div>
                    <div x-show="sidebarOpen || mobileMenuOpen" class="truncate">
                        <p class="text-xs font-bold text-[#EDF4FC] truncate">{{ auth()->user()->name ?? 'Command Operator' }}</p>
                        <p class="text-[10px] text-[#34B9D4] font-mono">
                            {{ auth()->user()->isSuperAdmin() ? 'LDRRMO Super Admin' : 'DIAL DAPH Command Center' }}
                        </p>
                        @if(auth()->user()->isSuperAdmin())
                            <a href="{{ route('super-admin.dashboard') }}" class="inline-block mt-0.5 text-[10px] font-bold text-purple-300 hover:underline">
                                &larr; Super Admin Console
                            </a>
                        @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="p-2 rounded-xl text-[#93A4B8] hover:text-[#DB4545] hover:bg-white/10 transition-colors"
                            aria-label="Sign Out"
                            title="Sign Out">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main Workspace --}}
    <div
        class="flex-1 flex flex-col min-h-screen transition-all duration-300 min-w-0 eoc-tactical-grid"
        :class="sidebarOpen ? 'lg:pl-64' : 'lg:pl-20'"
    >
        {{-- PWA Installation and Connection Status Banner --}}
        <x-pwa-install-banner />
        <x-pwa-connection-indicator />

        {{-- Top Command Bar --}}
        <header class="eoc-topbar sticky top-0 z-30 h-16 px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <button
                    type="button"
                    x-on:click="mobileMenuOpen = true"
                    class="p-2 rounded-xl text-[#93A4B8] hover:bg-white/10 lg:hidden"
                    aria-label="Open Navigation Menu"
                >
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>

                {{-- Live System Connection Status Pill --}}
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-full border text-[11px] font-bold"
                     :class="{
                         'bg-[#19865B]/15 border-[#19865B]/35 text-[#19865B]': $store.pwa.isOnline && $store.pwa.status !== 'syncing',
                         'bg-amber-500/15 border-amber-500/35 text-amber-400': $store.pwa.status === 'reconnecting',
                         'bg-blue-500/15 border-blue-500/35 text-blue-400': $store.pwa.status === 'syncing',
                         'bg-[#DB4545]/15 border-[#DB4545]/35 text-[#DB4545]': !$store.pwa.isOnline || $store.pwa.status === 'sync_failed'
                     }">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                              :class="{
                                  'bg-[#19865B]': $store.pwa.isOnline && $store.pwa.status !== 'syncing',
                                  'bg-amber-400': $store.pwa.status === 'reconnecting',
                                  'bg-blue-400': $store.pwa.status === 'syncing',
                                  'bg-[#DB4545]': !$store.pwa.isOnline || $store.pwa.status === 'sync_failed'
                              }"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2"
                              :class="{
                                  'bg-[#19865B]': $store.pwa.isOnline && $store.pwa.status !== 'syncing',
                                  'bg-amber-400': $store.pwa.status === 'reconnecting',
                                  'bg-blue-400': $store.pwa.status === 'syncing',
                                  'bg-[#DB4545]': !$store.pwa.isOnline || $store.pwa.status === 'sync_failed'
                              }"></span>
                    </span>
                    <span class="truncate" x-text="$store.pwa.isOnline ? 'Hinoba-an EOC Operational' : 'EOC Offline — Snapshot Mode'"></span>
                </div>

                @if((int) $telemetry['critical_active'] > 0)
                    <a href="{{ route('admin.incidents.index', ['priority' => 'critical']) }}"
                       class="hidden md:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#DB4545]/15 border border-[#DB4545]/45 text-[#DB4545] text-[11px] font-black eoc-glow-critical">
                        <span class="w-2 h-2 rounded-full bg-[#DB4545] animate-pulse"></span>
                        <span>{{ $telemetry['critical_active'] }} CRITICAL ACTIVE</span>
                    </a>
                @endif
            </div>

            {{-- Right Command Controls --}}
            <div class="flex items-center gap-2 sm:gap-3">
                {{-- Live Date & Clock --}}
                <div class="hidden xl:flex items-center gap-2 font-mono text-[11px] px-3 py-1.5 rounded-xl border border-slate-400/20 bg-slate-900/10"
                     x-data="{ nowStr: '' }"
                     x-init="nowStr = new Date().toLocaleString('en-PH', { weekday: 'short', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit' }); setInterval(() => nowStr = new Date().toLocaleString('en-PH', { weekday: 'short', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit' }), 1000)">
                    <i data-lucide="clock" class="w-3.5 h-3.5 text-[#34B9D4]"></i>
                    <span x-text="nowStr"></span>
                </div>

                {{-- Persistent Dark / Light Command Theme Switcher --}}
                <button
                    type="button"
                    x-on:click="toggleTheme()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-400/25 hover:border-[#34B9D4]/50 text-xs font-bold transition-colors"
                    :aria-label="isDark ? 'Switch to Light Mode' : 'Switch to Dark Command Mode'"
                    title="Toggle Dark / Light Mode"
                >
                    <i x-show="isDark" data-lucide="sun" class="w-4 h-4 text-[#F0A82E]"></i>
                    <i x-show="!isDark" data-lucide="moon" class="w-4 h-4 text-[#3187D8]"></i>
                    <span class="hidden sm:inline" x-text="isDark ? 'Light' : 'Dark'"></span>
                </button>

                {{-- Notification Center Drawer Trigger --}}
                <button
                    type="button"
                    x-on:click="openNotifications()"
                    class="relative p-2 rounded-xl border border-slate-400/25 hover:border-[#34B9D4]/50 transition-colors"
                    aria-label="Open Notification Center"
                    title="Command Notification Center"
                >
                    <i data-lucide="bell" class="w-4 h-4"></i>
                    <span x-show="unreadNotifications > 0"
                          id="eoc-notif-badge"
                          x-text="unreadNotifications > 99 ? '99+' : unreadNotifications"
                          class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1 rounded-full bg-[#DB4545] text-white font-mono text-[9px] font-black flex items-center justify-center shadow-md"
                          x-cloak>
                    </span>
                </button>

                {{-- Direct Operations Map Shortcut --}}
                <a href="{{ route('admin.map') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-gradient-to-r from-[#3187D8] to-[#2770b6] hover:from-[#34B9D4] hover:to-[#3187D8] text-white text-xs font-bold shadow-md transition-all">
                    <i data-lucide="radar" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">Live Map</span>
                </a>
            </div>
        </header>

        {{-- Page Content Slot --}}
        <main id="eoc-main-content" class="flex-1 p-4 sm:p-6 lg:p-8 max-w-[1600px] w-full mx-auto min-w-0">
            {{ $slot }}
        </main>
    </div>

    {{-- Admin Notification Center Slide-Over Drawer (100% Real DB Records) --}}
    <div x-show="notifOpen"
         x-transition.opacity
         x-on:click="notifOpen = false"
         class="fixed inset-0 z-[1050] bg-[#0B1726]/65 backdrop-blur-xs"
         x-cloak></div>

    <aside
        id="eoc-notification-drawer"
        x-show="notifOpen"
        x-transition:enter="transition ease-out duration-250 transform"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        aria-label="Command Notification Center"
        class="fixed inset-y-0 right-0 z-[1060] w-full sm:w-[410px] bg-[#101E2E] text-[#EDF4FC] border-l border-slate-400/20 shadow-2xl flex flex-col"
        x-cloak
    >
        <div class="p-4 border-b border-white/10 flex items-center justify-between bg-[#0B1726]">
            <div>
                <h2 class="text-sm font-black tracking-wide flex items-center gap-2">
                    <i data-lucide="bell-ring" class="w-4 h-4 text-[#34B9D4]"></i>
                    <span>EOC Notification Center</span>
                </h2>
                <p class="text-[11px] text-[#93A4B8] mt-0.5">
                    Real-time alerts · <span class="font-mono font-bold text-[#EDF4FC]" x-text="unreadNotifications"></span> unread
                </p>
            </div>
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-white/10 hover:bg-white/20 text-[10px] font-bold text-[#34B9D4]">
                        Mark All Read
                    </button>
                </form>
                <button type="button" x-on:click="notifOpen = false" class="p-1.5 rounded-lg text-[#93A4B8] hover:text-white hover:bg-white/10">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto alerto-scrollbar p-4 space-y-2.5">
            @forelse($telemetry['notifications'] as $notif)
                <div class="eoc-notif-item p-3.5 rounded-2xl border transition-colors {{ $notif->is_read ? 'bg-[#0B1726]/50 border-white/10 opacity-75' : 'bg-[#173957]/60 border-[#3187D8]/45' }}">
                    <div class="flex items-start justify-between gap-2">
                        <span class="px-2 py-0.5 rounded text-[9px] font-mono font-bold uppercase {{ str_contains($notif->type, 'critical') || str_contains($notif->type, 'reject') ? 'bg-[#DB4545]/20 text-[#DB4545]' : 'bg-[#3187D8]/20 text-[#34B9D4]' }}">
                            {{ str_replace('_', ' ', $notif->type) }}
                        </span>
                        <span class="text-[10px] font-mono text-[#93A4B8]">
                            {{ $notif->created_at?->diffForHumans(short: true) }}
                        </span>
                    </div>
                    <h4 class="text-xs font-bold text-[#EDF4FC] mt-1.5">{{ $notif->title }}</h4>
                    <p class="text-[11px] text-[#93A4B8] mt-0.5 leading-relaxed">{{ $notif->body }}</p>

                    <div class="flex items-center justify-between gap-2 mt-2.5 pt-2 border-t border-white/10 text-[10px]">
                        @if($notif->action_url)
                            <a href="{{ $notif->action_url }}" class="font-bold text-[#34B9D4] hover:underline flex items-center gap-1">
                                <span>Inspect Record</span>
                                <i data-lucide="arrow-up-right" class="w-3 h-3"></i>
                            </a>
                        @else
                            <span></span>
                        @endif

                        @if(! $notif->is_read)
                            <form method="POST" action="{{ route('admin.notifications.read', $notif->id) }}">
                                @csrf
                                <button type="submit" class="font-bold text-[#19865B] hover:underline">Mark Read</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-[#93A4B8] text-xs space-y-2">
                    <i data-lucide="shield-check" class="w-8 h-8 mx-auto opacity-50 text-[#19865B]"></i>
                    <p class="font-semibold text-[#EDF4FC]">All Command Alerts Clear</p>
                    <p class="text-[11px]">No notification records are currently queued for your Admin account.</p>
                </div>
            @endforelse
        </div>

        <div class="p-3 border-t border-white/10 bg-[#0B1726]">
            <a href="{{ route('admin.notifications.index') }}"
               class="w-full py-2 rounded-xl bg-[#173957] hover:bg-[#1f4b72] text-white text-xs font-bold flex items-center justify-center gap-1.5">
                <span>View Full Notification Log</span>
                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>
    </aside>

    <x-toast />

    <script>
        function eocShellController(config) {
            return {
                sidebarOpen: true,
                mobileMenuOpen: false,
                notifOpen: false,
                isDark: true,
                unreadNotifications: config.unreadNotifications || 0,
                criticalCount: config.criticalCount || 0,
                pendingIncidents: config.pendingIncidents || 0,

                initShell() {
                    try {
                        const savedSidebar = localStorage.getItem('alerto_sidebar_open');
                        if (savedSidebar === '0') this.sidebarOpen = false;
                    } catch (e) {}

                    if (window.AlertoEoc?.theme) {
                        this.isDark = window.AlertoEoc.theme.isDark();
                    }

                    window.addEventListener('eoc:theme-changed', (e) => {
                        this.isDark = !!e.detail?.isDark;
                    });
                },

                toggleTheme() {
                    if (window.AlertoEoc?.theme) {
                        const next = window.AlertoEoc.theme.toggle();
                        this.isDark = next === 'dark';
                    }
                },

                openNotifications() {
                    this.notifOpen = true;
                    this.$nextTick(() => {
                        if (window.refreshIcons) window.refreshIcons();
                        const drawer = document.getElementById('eoc-notification-drawer');
                        if (drawer && window.AlertoEoc?.notifications) {
                            window.AlertoEoc.notifications.animateDrawerOpen(drawer);
                        }
                    });
                }
            };
        }
    </script>

    @stack('scripts')
</body>
</html>
