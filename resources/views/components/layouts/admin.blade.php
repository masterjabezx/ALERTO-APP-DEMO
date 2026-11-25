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
    data-default-theme="dark"
    class="alerto-shell eoc-shell theme-dark h-full flex antialiased selection:bg-[#E87524]/30 selection:text-white"
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
                const saved = localStorage.getItem('alerto_portal_theme') || localStorage.getItem('alerto_admin_theme');
                const mode = saved === 'light' ? 'light' : 'dark';
                const shell = document.getElementById('eoc-admin-shell');
                if (shell) {
                    shell.classList.remove('theme-dark', 'theme-light');
                    shell.classList.add('theme-' + mode);
                }
                document.documentElement.classList.toggle('dark', mode === 'dark');
            } catch (e) {}
        })();
    </script>

    {{-- Skip to Content Link for Keyboard Accessibility --}}
    <a href="#eoc-main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[1200] focus:px-4 focus:py-2 focus:rounded-xl focus:bg-[#E87524] focus:text-white focus:font-bold focus:text-xs">
        Skip to main command workspace
    </a>

    {{-- Mobile Backdrop --}}
    <div x-show="mobileMenuOpen"
         x-transition.opacity
         x-on:click="mobileMenuOpen = false"
         class="fixed inset-0 z-40 bg-[#101214]/80 backdrop-blur-xs lg:hidden"
         x-cloak></div>

    {{-- Admin Command Center Carbon Black Sidebar --}}
    <aside
        id="eoc-sidebar"
        aria-label="Admin Command Center Navigation"
        class="fixed inset-y-0 left-0 z-50 bg-[#17191C] text-[#F5F3EF] flex flex-col transition-all duration-300 ease-out border-r border-white/10 shadow-2xl"
        :class="[
            sidebarOpen ? 'lg:w-64' : 'lg:w-20',
            mobileMenuOpen ? 'translate-x-0 w-72' : '-translate-x-full lg:translate-x-0'
        ]"
    >
        {{-- Brand / Header --}}
        <div class="h-16 px-4 flex items-center justify-between border-b border-white/10 shrink-0 bg-[#101214]/90">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 overflow-hidden focus:outline-hidden">
                <img src="/images/branding/alerto-logo-web-128.png" alt="ALERTO APP" class="w-10 h-10 rounded-xl object-contain shrink-0">
                <div x-show="sidebarOpen || mobileMenuOpen" class="truncate">
                    <span class="font-extrabold text-[#F5F3EF] tracking-wider text-sm block">DIAL DAPH</span>
                    <span class="text-[10px] text-[#E87524] font-mono tracking-widest block">COMMAND CENTER</span>
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
                        ['label' => 'Internal Messenger', 'icon' => 'messages-square', 'route' => 'admin.messages.index', 'match' => 'admin.messages.*', 'badge' => $telemetry['internal_unread'], 'badgeColor' => 'bg-[#E87524] text-white'],
                        ['label' => 'Facebook Messenger', 'icon' => 'message-circle', 'route' => 'admin.communications.fb.inbox', 'match' => 'admin.communications.fb.*', 'badge' => $telemetry['fb_unread_total'], 'badgeColor' => 'bg-[#397BA8] text-white'],
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
                            class="eoc-nav-link relative flex items-center justify-between gap-2.5 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all group {{ $isActive ? 'bg-gradient-to-r from-[#E87524] to-[#B94F12] text-white shadow-md shadow-[#E87524]/25 font-bold' : 'text-[#9E9993] hover:text-[#F5F3EF] hover:bg-white/5' }}"
                            :title="(!sidebarOpen && !mobileMenuOpen) ? '{{ $item['label'] }}' : ''"
                        >
                            @if($isActive)
                                <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-[#FFF0E4]"></span>
                            @endif

                            <div class="flex items-center gap-3 min-w-0">
                                <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 shrink-0 {{ $isActive ? 'text-white' : 'text-[#9E9993] group-hover:text-[#F29A56]' }}"></i>
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

        {{-- Admin Sidebar Footer --}}
        <div class="p-3 border-t border-white/10 shrink-0 bg-[#101214]/90">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2.5 overflow-hidden">
                    <div class="w-9 h-9 rounded-xl bg-[#E87524]/20 text-[#F29A56] border border-[#E87524]/40 flex items-center justify-center font-black text-xs shrink-0">
                        EOC
                    </div>
                    <div x-show="sidebarOpen || mobileMenuOpen" class="truncate">
                        <p class="text-xs font-bold text-[#F5F3EF] truncate">{{ auth()->user()->name ?? 'Command Operator' }}</p>
                        <p class="text-[10px] text-[#F29A56] font-mono">
                            {{ auth()->user()->isSuperAdmin() ? 'LDRRMO Super Admin' : 'DIAL DAPH Command Center' }}
                        </p>
                        @if(auth()->user()->isSuperAdmin())
                            <a href="{{ route('super-admin.dashboard') }}" class="inline-block mt-0.5 text-[10px] font-bold text-[#E87524] hover:underline">
                                &larr; Super Admin Console
                            </a>
                        @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="p-2 rounded-xl text-[#9E9993] hover:text-[#C83F43] hover:bg-white/10 transition-colors"
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
        <header class="eoc-topbar sticky top-0 z-30">
            <x-demo-environment-banner />
            <div class="h-16 px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <button
                    type="button"
                    x-on:click="mobileMenuOpen = true"
                    class="p-2 rounded-xl text-[#9E9993] hover:bg-black/5 dark:hover:bg-white/10 lg:hidden"
                    aria-label="Open Navigation Menu"
                >
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <img src="/images/branding/alerto-logo-web-128.png" alt="ALERTO APP" class="w-8 h-8 rounded-lg object-contain lg:hidden shrink-0">

                {{-- Live System Connection Status Pill --}}
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-full border text-[11px] font-bold"
                     :class="{
                         'bg-[#27845B]/15 border-[#27845B]/35 text-[#27845B]': $store.pwa.isOnline && $store.pwa.status !== 'syncing',
                         'bg-[#D99024]/15 border-[#D99024]/35 text-[#D99024]': $store.pwa.status === 'reconnecting',
                         'bg-[#397BA8]/15 border-[#397BA8]/35 text-[#397BA8]': $store.pwa.status === 'syncing',
                         'bg-[#C83F43]/15 border-[#C83F43]/35 text-[#C83F43]': !$store.pwa.isOnline || $store.pwa.status === 'sync_failed'
                     }">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                              :class="{
                                  'bg-[#27845B]': $store.pwa.isOnline && $store.pwa.status !== 'syncing',
                                  'bg-[#D99024]': $store.pwa.status === 'reconnecting',
                                  'bg-[#397BA8]': $store.pwa.status === 'syncing',
                                  'bg-[#C83F43]': !$store.pwa.isOnline || $store.pwa.status === 'sync_failed'
                              }"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2"
                              :class="{
                                  'bg-[#27845B]': $store.pwa.isOnline && $store.pwa.status !== 'syncing',
                                  'bg-[#D99024]': $store.pwa.status === 'reconnecting',
                                  'bg-[#397BA8]': $store.pwa.status === 'syncing',
                                  'bg-[#C83F43]': !$store.pwa.isOnline || $store.pwa.status === 'sync_failed'
                              }"></span>
                    </span>
                    <span class="truncate" x-text="$store.pwa.isOnline ? 'Hinoba-an EOC Operational' : 'EOC Offline — Snapshot Mode'"></span>
                </div>

                @if((int) $telemetry['critical_active'] > 0)
                    <a href="{{ route('admin.incidents.index', ['priority' => 'critical']) }}"
                       class="hidden md:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#C83F43]/15 border border-[#C83F43]/45 text-[#C83F43] text-[11px] font-black eoc-glow-critical">
                        <span class="w-2 h-2 rounded-full bg-[#C83F43] animate-pulse"></span>
                        <span>{{ $telemetry['critical_active'] }} CRITICAL ACTIVE</span>
                    </a>
                @endif
            </div>

            {{-- Right Command Controls --}}
            <div class="flex items-center gap-2 sm:gap-3">
                {{-- Live Date & Clock --}}
                <div class="hidden xl:flex items-center gap-2 font-mono text-[11px] px-3 py-1.5 rounded-xl border border-black/10 dark:border-white/15 bg-black/5 dark:bg-white/5"
                     x-data="{ nowStr: '' }"
                     x-init="nowStr = new Date().toLocaleString('en-PH', { weekday: 'short', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit' }); setInterval(() => nowStr = new Date().toLocaleString('en-PH', { weekday: 'short', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit' }), 1000)">
                    <i data-lucide="clock" class="w-3.5 h-3.5 text-[#E87524]"></i>
                    <span x-text="nowStr"></span>
                </div>

                {{-- Persistent Dark / Light Command Theme Switcher --}}
                <x-theme-toggle />

                {{-- Notification Center Drawer Trigger --}}
                <button
                    type="button"
                    x-on:click="openNotifications()"
                    class="relative p-2 rounded-xl border border-black/10 dark:border-white/15 hover:border-[#E87524]/60 transition-colors"
                    aria-label="Open Notification Center"
                    title="Command Notification Center"
                >
                    <i data-lucide="bell" class="w-4 h-4"></i>
                    <span x-show="unreadNotifications > 0"
                          id="eoc-notif-badge"
                          x-text="unreadNotifications > 99 ? '99+' : unreadNotifications"
                          class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1 rounded-full bg-[#C83F43] text-white font-mono text-[9px] font-black flex items-center justify-center shadow-md"
                          x-cloak>
                    </span>
                </button>

                {{-- Direct Operations Map Shortcut --}}
                <a href="{{ route('admin.map') }}"
                   class="alerto-btn-press inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-gradient-to-r from-[#E87524] to-[#B94F12] hover:from-[#F29A56] hover:to-[#E87524] text-white text-xs font-bold shadow-md transition-all">
                    <i data-lucide="radar" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">Live Map</span>
                </a>
            </div>
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
         class="fixed inset-0 z-[1050] bg-[#101214]/70 backdrop-blur-xs"
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
        class="fixed inset-y-0 right-0 z-[1060] w-full sm:w-[410px] bg-[#17191C] text-[#F5F3EF] border-l border-white/10 shadow-2xl flex flex-col"
        x-cloak
    >
        <div class="p-4 border-b border-white/10 flex items-center justify-between bg-[#101214]">
            <div>
                <h2 class="text-sm font-black tracking-wide flex items-center gap-2">
                    <i data-lucide="bell-ring" class="w-4 h-4 text-[#E87524]"></i>
                    <span>EOC Notification Center</span>
                </h2>
                <p class="text-[11px] text-[#9E9993] mt-0.5">
                    Real-time alerts · <span class="font-mono font-bold text-[#F5F3EF]" x-text="unreadNotifications"></span> unread
                </p>
            </div>
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-white/10 hover:bg-white/20 text-[10px] font-bold text-[#F29A56]">
                        Mark All Read
                    </button>
                </form>
                <button type="button" x-on:click="notifOpen = false" class="p-1.5 rounded-lg text-[#9E9993] hover:text-white hover:bg-white/10">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto alerto-scrollbar p-4 space-y-2.5">
            @forelse($telemetry['notifications'] as $notif)
                <div class="eoc-notif-item p-3.5 rounded-2xl border transition-colors {{ $notif->is_read ? 'bg-[#101214]/60 border-white/10 opacity-75' : 'bg-[#292D32]/80 border-[#E87524]/45' }}">
                    <div class="flex items-start justify-between gap-2">
                        <span class="px-2 py-0.5 rounded text-[9px] font-mono font-bold uppercase {{ str_contains($notif->type, 'critical') || str_contains($notif->type, 'reject') ? 'bg-[#C83F43]/20 text-[#C83F43]' : 'bg-[#E87524]/20 text-[#F29A56]' }}">
                            {{ str_replace('_', ' ', $notif->type) }}
                        </span>
                        <span class="text-[10px] font-mono text-[#9E9993]">
                            {{ $notif->created_at?->diffForHumans(short: true) }}
                        </span>
                    </div>
                    <h4 class="text-xs font-bold text-[#F5F3EF] mt-1.5">{{ $notif->title }}</h4>
                    <p class="text-[11px] text-[#9E9993] mt-0.5 leading-relaxed">{{ $notif->body }}</p>

                    <div class="flex items-center justify-between gap-2 mt-2.5 pt-2 border-t border-white/10 text-[10px]">
                        @if($notif->action_url)
                            <a href="{{ $notif->action_url }}" class="font-bold text-[#F29A56] hover:underline flex items-center gap-1">
                                <span>Inspect Record</span>
                                <i data-lucide="arrow-up-right" class="w-3 h-3"></i>
                            </a>
                        @else
                            <span></span>
                        @endif

                        @if(! $notif->is_read)
                            <form method="POST" action="{{ route('admin.notifications.read', $notif->id) }}">
                                @csrf
                                <button type="submit" class="font-bold text-[#27845B] hover:underline">Mark Read</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-[#9E9993] text-xs space-y-2">
                    <i data-lucide="shield-check" class="w-8 h-8 mx-auto opacity-50 text-[#27845B]"></i>
                    <p class="font-semibold text-[#F5F3EF]">All Command Alerts Clear</p>
                    <p class="text-[11px]">No notification records are currently queued for your Admin account.</p>
                </div>
            @endforelse
        </div>

        <div class="p-3 border-t border-white/10 bg-[#101214]">
            <a href="{{ route('admin.notifications.index') }}"
               class="alerto-btn-press w-full py-2 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white text-xs font-bold flex items-center justify-center gap-1.5">
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

                    const syncTheme = (e) => {
                        this.isDark = !!e.detail?.isDark;
                    };
                    window.addEventListener('eoc:theme-changed', syncTheme);
                    window.addEventListener('alerto:theme-changed', syncTheme);
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
