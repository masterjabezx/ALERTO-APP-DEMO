<x-layouts.admin :title="'ALERTO EOC — Command & Coordination Center'">
    <div
        class="space-y-6"
        x-data="adminCommandCenter({
            stats: {{ Js::from($stats) }},
            presence: {{ Js::from($presence) }},
            charts: {{ Js::from($charts) }},
            liveActivity: {{ Js::from($liveActivity) }}
        })"
        x-init="initCommandCenter()"
    >
        {{-- A. ANIMATED HERO HEADER WITH 3D TELEMETRY SPHERE & AMBIENT PARTICLE FIELD --}}
        <section class="alerto-hero-section eoc-hero-section relative rounded-[24px] overflow-hidden border border-[#E87524]/30 bg-gradient-to-br from-[#101214] via-[#17191C] to-[#292D32] text-[#F5F3EF] shadow-2xl">
            {{-- Ambient Canvas Particle Field (FPS-throttled & paused when hidden) --}}
            <canvas id="eoc-ambient-particles" class="absolute inset-0 w-full h-full pointer-events-none opacity-80" aria-hidden="true"></canvas>

            {{-- Subtle Technical Grid & Scanline --}}
            <div class="absolute inset-0 eoc-tactical-grid pointer-events-none opacity-60" aria-hidden="true"></div>
            <div class="eoc-scanline absolute inset-x-0 h-20 bg-gradient-to-b from-transparent via-[#E87524]/10 to-transparent pointer-events-none" aria-hidden="true"></div>

            <div class="relative z-10 p-6 lg:p-8 grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                {{-- Left Brand & EOC Identity --}}
                <div class="lg:col-span-8 space-y-4">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#27845B]/20 border border-[#27845B]/45 text-[#5BD19A] text-[11px] font-mono font-bold uppercase tracking-wider">
                            <span class="w-2 h-2 rounded-full bg-[#27845B] animate-ping"></span>
                            LIVE SYSTEM TELEMETRY · CONNECTED
                        </span>
                        <span class="px-3 py-1 rounded-full bg-white/10 border border-white/15 text-[#CFCBC4] text-[11px] font-mono uppercase">
                            ALERTO EOC · Hinoba-an LDRRMO
                        </span>
                        @if(($stats['critical_count'] ?? 0) > 0)
                            <span class="px-3 py-1 rounded-full bg-[#C83F43]/25 border border-[#C83F43]/60 text-[#FCA5A5] text-[11px] font-mono font-black eoc-glow-critical">
                                &#9888; {{ $stats['critical_count'] }} CRITICAL ALERT ACTIVE
                            </span>
                        @endif
                    </div>

                    <div>
                        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black tracking-tight text-white">
                            DIAL DAPH Command Center
                        </h1>
                        <p class="text-xs sm:text-sm text-[#CFCBC4] max-w-2xl mt-1.5 leading-relaxed">
                            Real-time municipal emergency receiving, call verification, SOS response, multi-agency coordination, and live telemetry for Hinoba-an, Negros Occidental.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5 pt-1">
                        <a href="{{ route('admin.incoming.index') }}"
                           class="alerto-btn-press inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#E87524] hover:bg-[#F29A56] text-white font-black text-xs shadow-lg shadow-[#E87524]/25 transition-all">
                            <i data-lucide="phone-call" class="w-4 h-4"></i>
                            <span>Incoming Reports ({{ $stats['incoming_reports'] ?? 0 }})</span>
                        </a>
                        <a href="{{ route('admin.sos.index') }}"
                           class="alerto-btn-press inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#C83F43] hover:bg-[#A63034] text-white font-black text-xs shadow-lg shadow-[#C83F43]/25 transition-all">
                            <i data-lucide="siren" class="w-4 h-4"></i>
                            <span>SOS Alerts ({{ $stats['active_sos'] ?? 0 }})</span>
                        </a>
                        <a href="{{ route('admin.dispatch.index') }}"
                           class="alerto-btn-press inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#292D32] hover:bg-[#454A50] border border-white/15 text-white font-bold text-xs shadow-md transition-all">
                            <i data-lucide="send" class="w-4 h-4 text-[#F29A56]"></i>
                            <span>Dispatch Console</span>
                        </a>
                        <a href="{{ route('admin.map') }}"
                           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 border border-white/20 text-[#F5F3EF] font-bold text-xs transition-all">
                            <i data-lucide="map-pin" class="w-4 h-4 text-[#E87524]"></i>
                            <span>Live Operations Map</span>
                        </a>
                    </div>
                </div>

                {{-- Right: Interactive 3D Three.js WebGL Operations Telemetry Sphere (Decorative HUD) --}}
                <div class="lg:col-span-4 flex flex-col items-center justify-center">
                    <div class="relative w-52 h-52 sm:w-56 sm:h-56 rounded-full bg-[#101214]/85 border border-[#E87524]/35 flex items-center justify-center shadow-inner eoc-glow-info overflow-hidden"
                         title="Interactive 3D Telemetry HUD — Drag to rotate. Actual geographic pins are mapped in Live Operations Map.">
                        <canvas id="eoc-globe-canvas" class="w-full h-full cursor-grab active:cursor-grabbing"></canvas>
                        <div class="absolute bottom-2.5 px-2.5 py-0.5 rounded-full bg-[#101214]/90 border border-white/15 text-[9px] font-mono text-[#F29A56] tracking-wider pointer-events-none">
                            3D EOC TELEMETRY HUD · <span x-text="globeMode === 'webgl' ? 'WEBGL' : '2D RADAR'">WEBGL</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- B-PRE. URGENT SOS & INCOMING UNCLASSIFIED REPORTS PANELS --}}
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <div class="alerto-card p-5 border border-[#C83F43]/35 space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-black uppercase tracking-wider text-[#C83F43] flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#C83F43] animate-ping"></span>
                        Active SOS Alerts Queue ({{ $stats['active_sos'] ?? 0 }})
                    </h2>
                    <a href="{{ route('admin.sos.index') }}" class="text-xs font-bold text-[#B94F12] dark:text-[#F29A56] hover:underline">Open SOS Console &rarr;</a>
                </div>
                @forelse(($activeSosAlerts ?? collect()) as $sos)
                    <a href="{{ route('admin.sos.show', $sos->id) }}" class="block p-3 rounded-xl bg-[#C83F43]/10 hover:bg-[#C83F43]/20 border border-[#C83F43]/30 transition-all text-xs">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono font-black">{{ $sos->reference_number }} · {{ strtoupper($sos->sender_type) }} SOS</span>
                            <span class="px-2 py-0.5 rounded bg-[#C83F43] text-white text-[10px] font-bold uppercase">{{ str_replace('_', ' ', $sos->status) }}</span>
                        </div>
                        <p class="text-[#77736E] dark:text-[#CFCBC4] mt-1">{{ $sos->sender_name }} ({{ $sos->sender_phone ?? 'no phone' }}) · {{ $sos->barangay?->name ?? 'Unverified Location' }}</p>
                    </a>
                @empty
                    <p class="text-xs text-[#77736E] py-4 text-center">No active SOS emergency alerts at this moment.</p>
                @endforelse
            </div>

            <div class="alerto-card p-5 border border-[#E87524]/35 space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-black uppercase tracking-wider text-[#B94F12] dark:text-[#F29A56] flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#E87524]"></span>
                        Incoming Unclassified Reports ({{ $stats['incoming_reports'] ?? 0 }})
                    </h2>
                    <a href="{{ route('admin.incoming.index') }}" class="text-xs font-bold text-[#B94F12] dark:text-[#F29A56] hover:underline">Open Call Queue &rarr;</a>
                </div>
                @forelse(($incomingReports ?? collect()) as $rep)
                    <a href="{{ route('admin.incidents.show', $rep->reference_number) }}" class="block p-3 rounded-xl bg-[#E87524]/10 hover:bg-[#E87524]/20 border border-[#E87524]/30 transition-all text-xs">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono font-black">{{ $rep->reference_number }}</span>
                            <span class="px-2 py-0.5 rounded bg-[#E87524] text-white text-[10px] font-bold uppercase">{{ str_replace('_', ' ', $rep->workflow_stage ?? 'new_report') }}</span>
                        </div>
                        <p class="text-[#77736E] dark:text-[#CFCBC4] mt-1">{{ $rep->caller_name }} · {{ $rep->caller_phone }} · {{ $rep->barangay?->name ?? 'GPS Pin Pending Verification' }}</p>
                    </a>
                @empty
                    <p class="text-xs text-[#77736E] py-4 text-center">All incoming reports have been called and classified.</p>
                @endforelse
            </div>
        </section>

        {{-- B. ADVANCED 10-METRIC REAL-TIME DATABASE STATISTICS GRID --}}
        <section aria-label="Operational Statistics">
            {{-- 7 Core Incident Lifecycle Metrics --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3.5">
                {{-- 1. Total Incidents --}}
                <div class="alerto-kpi-card eoc-kpi-card eoc-card p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#9E9993]">Total Incidents</span>
                        <span class="w-8 h-8 rounded-xl bg-[#E87524]/15 text-[#E87524] flex items-center justify-center">
                            <i data-lucide="layers" class="w-4 h-4"></i>
                        </span>
                    </div>
                    <div class="mt-2">
                        <p class="text-2xl sm:text-3xl font-black font-mono" data-eoc-count="{{ $stats['total'] ?? 0 }}" x-text="stats.total">{{ $stats['total'] ?? 0 }}</p>
                        @if(($stats['trend_percent'] ?? null) !== null)
                            <span class="text-[10px] font-mono {{ $stats['trend_percent'] >= 0 ? 'text-[#E87524]' : 'text-[#27845B]' }}">
                                {{ $stats['trend_percent'] >= 0 ? '+' : '' }}{{ $stats['trend_percent'] }}% vs prior 7d
                            </span>
                        @else
                            <span class="text-[10px] text-[#9E9993] font-mono">All records</span>
                        @endif
                    </div>
                </div>

                {{-- 2. Pending Reports --}}
                <div class="alerto-kpi-card eoc-kpi-card eoc-card p-4 flex flex-col justify-between border-[#D99024]/35">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#D99024]">Pending</span>
                        <span class="w-8 h-8 rounded-xl bg-[#D99024]/15 text-[#D99024] flex items-center justify-center">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                        </span>
                    </div>
                    <div class="mt-2">
                        <p class="text-2xl sm:text-3xl font-black font-mono text-[#D99024]" data-eoc-count="{{ $stats['pending_review'] ?? 0 }}" x-text="stats.pending_review">{{ $stats['pending_review'] ?? 0 }}</p>
                        <span class="text-[10px] text-[#9E9993] font-mono">Needs intake review</span>
                    </div>
                </div>

                {{-- 3. Accepted Incidents --}}
                <div class="alerto-kpi-card eoc-kpi-card eoc-card p-4 flex flex-col justify-between border-[#397BA8]/35">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#397BA8]">Accepted</span>
                        <span class="w-8 h-8 rounded-xl bg-[#397BA8]/15 text-[#397BA8] flex items-center justify-center">
                            <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                        </span>
                    </div>
                    <div class="mt-2">
                        <p class="text-2xl sm:text-3xl font-black font-mono text-[#397BA8]" data-eoc-count="{{ $stats['accepted'] ?? 0 }}" x-text="stats.accepted">{{ $stats['accepted'] ?? 0 }}</p>
                        <span class="text-[10px] text-[#9E9993] font-mono">Verified queue</span>
                    </div>
                </div>

                {{-- 4. Dispatched Incidents --}}
                <div class="alerto-kpi-card eoc-kpi-card eoc-card p-4 flex flex-col justify-between border-[#E87524]/35">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#E87524]">Dispatched</span>
                        <span class="w-8 h-8 rounded-xl bg-[#E87524]/15 text-[#E87524] flex items-center justify-center">
                            <i data-lucide="send" class="w-4 h-4"></i>
                        </span>
                    </div>
                    <div class="mt-2">
                        <p class="text-2xl sm:text-3xl font-black font-mono text-[#E87524]" data-eoc-count="{{ $stats['dispatched'] ?? 0 }}" x-text="stats.dispatched">{{ $stats['dispatched'] ?? 0 }}</p>
                        <span class="text-[10px] text-[#9E9993] font-mono">Units mobilized</span>
                    </div>
                </div>

                {{-- 5. Active Incidents --}}
                <div class="alerto-kpi-card eoc-kpi-card eoc-card p-4 flex flex-col justify-between {{ ($stats['critical_count'] ?? 0) > 0 ? 'eoc-glow-critical border-[#C83F43]/50' : '' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#C83F43]">Active Field</span>
                        <span class="w-8 h-8 rounded-xl bg-[#C83F43]/15 text-[#C83F43] flex items-center justify-center">
                            <i data-lucide="activity" class="w-4 h-4"></i>
                        </span>
                    </div>
                    <div class="mt-2">
                        <p class="text-2xl sm:text-3xl font-black font-mono text-[#C83F43]" data-eoc-count="{{ $stats['active_incidents'] ?? 0 }}" x-text="stats.active_incidents">{{ $stats['active_incidents'] ?? 0 }}</p>
                        <span class="text-[10px] text-[#9E9993] font-mono">On scene / en route</span>
                    </div>
                </div>

                {{-- 6. Resolved Incidents --}}
                <div class="alerto-kpi-card eoc-kpi-card eoc-card p-4 flex flex-col justify-between border-[#27845B]/35">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#27845B]">Resolved</span>
                        <span class="w-8 h-8 rounded-xl bg-[#27845B]/15 text-[#27845B] flex items-center justify-center">
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                        </span>
                    </div>
                    <div class="mt-2">
                        <p class="text-2xl sm:text-3xl font-black font-mono text-[#27845B]" data-eoc-count="{{ $stats['resolved'] ?? 0 }}" x-text="stats.resolved">{{ $stats['resolved'] ?? 0 }}</p>
                        <span class="text-[10px] text-[#9E9993] font-mono">Cleared &amp; closed</span>
                    </div>
                </div>

                {{-- 7. Rejected Incidents --}}
                <div class="alerto-kpi-card eoc-kpi-card eoc-card p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#9E9993]">Rejected</span>
                        <span class="w-8 h-8 rounded-xl bg-white/10 text-[#9E9993] flex items-center justify-center">
                            <i data-lucide="x-circle" class="w-4 h-4"></i>
                        </span>
                    </div>
                    <div class="mt-2">
                        <p class="text-2xl sm:text-3xl font-black font-mono" data-eoc-count="{{ $stats['rejected'] ?? 0 }}" x-text="stats.rejected">{{ $stats['rejected'] ?? 0 }}</p>
                        <span class="text-[10px] text-[#9E9993] font-mono">Declined reports</span>
                    </div>
                </div>
            </div>

            {{-- 3 Account Presence Metrics + Live Performance Readout --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 mt-3.5">
                <a href="{{ route('admin.monitor.users', ['account_type' => 'personnel', 'status' => 'online']) }}"
                   class="alerto-kpi-card eoc-kpi-card eoc-card p-4 flex items-center justify-between hover:scale-[1.01] transition-transform">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#9E9993] block">Personnel Online</span>
                        <span class="text-2xl font-black font-mono text-[#27845B] mt-1 block" data-eoc-count="{{ $presence['personnel_online'] ?? 0 }}" x-text="presence.personnel_online">{{ $presence['personnel_online'] ?? 0 }}</span>
                        <span class="text-[10px] text-[#9E9993] font-mono">Active account session</span>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-[#27845B]/15 text-[#27845B] flex items-center justify-center">
                        <i data-lucide="radio" class="w-5 h-5"></i>
                    </div>
                </a>

                <a href="{{ route('admin.monitor.users', ['account_type' => 'office', 'status' => 'online']) }}"
                   class="alerto-kpi-card eoc-kpi-card eoc-card p-4 flex items-center justify-between hover:scale-[1.01] transition-transform">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#9E9993] block">Offices Online</span>
                        <span class="text-2xl font-black font-mono text-[#E87524] mt-1 block" data-eoc-count="{{ $presence['offices_online'] ?? 0 }}" x-text="presence.offices_online">{{ $presence['offices_online'] ?? 0 }}</span>
                        <span class="text-[10px] text-[#9E9993] font-mono">Agency desk consoles</span>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-[#E87524]/15 text-[#E87524] flex items-center justify-center">
                        <i data-lucide="building-2" class="w-5 h-5"></i>
                    </div>
                </a>

                <a href="{{ route('admin.monitor.users', ['account_type' => 'citizen', 'status' => 'online']) }}"
                   class="alerto-kpi-card eoc-kpi-card eoc-card p-4 flex items-center justify-between hover:scale-[1.01] transition-transform">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#9E9993] block">Citizens Online</span>
                        <span class="text-2xl font-black font-mono text-[#397BA8] mt-1 block" data-eoc-count="{{ $presence['citizens_online'] ?? 0 }}" x-text="presence.citizens_online">{{ $presence['citizens_online'] ?? 0 }}</span>
                        <span class="text-[10px] text-[#9E9993] font-mono">Presence only · No GPS tracked</span>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-[#397BA8]/15 text-[#397BA8] flex items-center justify-center">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                </a>

                <div class="alerto-kpi-card eoc-kpi-card eoc-card p-4 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#9E9993] block">Avg Triage / Resolution</span>
                        <div class="text-lg font-black font-mono mt-1">
                            <span class="text-[#E87524]" x-text="(charts.response_performance?.avg_accept_minutes || 0) + 'm'">0m</span>
                            <span class="text-[#77736E] text-xs">/</span>
                            <span class="text-[#27845B]" x-text="(charts.response_performance?.avg_resolve_minutes || 0) + 'm'">0m</span>
                        </div>
                        <span class="text-[10px] text-[#9E9993] font-mono">Measured from DB timestamps</span>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-[#E87524]/15 text-[#E87524] flex items-center justify-center">
                        <i data-lucide="gauge" class="w-5 h-5"></i>
                    </div>
                </div>
            </div>
        </section>

        {{-- C. LIVE INCIDENT ACTIVITY STREAM + HIGH PRIORITY PANEL + GIS PREVIEW --}}
        <section class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- High-Priority & Triage Queue + GIS Preview (7 cols) --}}
            <div class="lg:col-span-7 space-y-6">
                {{-- Critical / High-Priority Incident Panel --}}
                <div class="alerto-reveal-panel eoc-reveal-panel eoc-card p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-white/10 pb-3">
                        <div class="flex items-center gap-2">
                            <i data-lucide="siren" class="w-4 h-4 text-[#C83F43]"></i>
                            <h2 class="text-sm font-black uppercase tracking-wider">Priority Emergency Queue</h2>
                        </div>
                        <a href="{{ route('admin.incidents.index', ['priority' => 'critical']) }}" class="text-xs font-bold text-[#E87524] hover:underline">
                            Open Queue &rarr;
                        </a>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @forelse($highPriorityIncidents as $hp)
                            <a href="{{ route('admin.incidents.show', $hp->reference_number) }}"
                               class="p-3.5 rounded-2xl border transition-all flex flex-col justify-between gap-2 {{ $hp->priority === 'critical' ? 'border-[#C83F43]/50 bg-[#C83F43]/10 hover:bg-[#C83F43]/15' : 'border-[#D99024]/35 bg-[#D99024]/5 hover:bg-[#D99024]/10' }}">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-mono text-xs font-black">{{ $hp->reference_number }}</span>
                                    <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase {{ $hp->priority === 'critical' ? 'bg-[#C83F43] text-white' : 'bg-[#D99024] text-[#17191C]' }}">
                                        {{ $hp->priority }}
                                    </span>
                                </div>
                                <div class="text-xs font-bold truncate">{{ $hp->category?->name ?? 'Emergency Incident' }}</div>
                                <div class="flex items-center justify-between text-[10px] text-[#9E9993] font-mono">
                                    <span>{{ $hp->barangay?->name ?? 'Hinoba-an' }}</span>
                                    <span>{{ strtoupper(str_replace('_', ' ', $hp->status)) }}</span>
                                </div>
                            </a>
                        @empty
                            <div class="col-span-full py-6 text-center text-xs text-[#9E9993]">
                                No active critical or high-priority incidents awaiting action.
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Interactive Live GIS Portal Gateway --}}
                <x-map-container height="h-[320px]" title="Hinoba-an LDRRMO Operations GIS Grid">
                    <div class="absolute bottom-4 right-4 z-10 p-3 rounded-2xl bg-[#101214]/92 border border-[#E87524]/35 text-[#F5F3EF] backdrop-blur-md shadow-xl text-xs space-y-2">
                        <div class="font-bold text-[#27845B] flex items-center gap-1.5">
                            <i data-lucide="radio" class="w-3.5 h-3.5"></i>
                            <span>Live Map Ready</span>
                        </div>
                        <p class="text-[11px] text-[#9E9993] font-mono">
                            Active Responders Online: <strong class="text-[#F5F3EF]" x-text="presence.personnel_online">{{ $presence['personnel_online'] ?? 0 }}</strong>
                        </p>
                        <a href="{{ route('admin.map') }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#E87524] hover:bg-[#F29A56] hover:text-[#17191C] text-white font-bold text-[11px] transition-colors">
                            <span>Launch Full Operations Map</span>
                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </x-map-container>
            </div>

            {{-- Real Database Live Incident & Responder Activity Feed (5 cols) --}}
            <div class="lg:col-span-5 alerto-reveal-panel eoc-reveal-panel eoc-card p-5 flex flex-col">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-white/10">
                    <h2 class="text-sm font-black uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="activity" class="w-4 h-4 text-[#E87524]"></i>
                        <span>Live EOC Activity Stream</span>
                    </h2>
                    <button type="button" x-on:click="refreshTelemetry()"
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[#E87524]/15 text-[#E87524] hover:bg-[#E87524]/25 text-[10px] font-mono font-bold">
                        <i data-lucide="refresh-cw" class="w-3 h-3" :class="refreshing ? 'animate-spin' : ''"></i>
                        <span x-text="refreshing ? 'SYNCING' : 'REALTIME DB'">REALTIME DB</span>
                    </button>
                </div>

                <div class="space-y-3 overflow-y-auto alerto-scrollbar flex-1 max-h-[500px] pr-1">
                    <template x-for="ev in liveActivity" :key="ev.id">
                        <a :href="ev.url"
                           class="block p-3.5 rounded-2xl border border-white/10 hover:border-[#E87524]/45 transition-all"
                           :class="{
                               'bg-[#C83F43]/10 border-[#C83F43]/35': ev.priority === 'critical',
                               'bg-[#E87524]/10': ev.kind === 'dispatch',
                               'bg-[#27845B]/10': ev.kind === 'personnel'
                           }">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-mono font-bold text-xs" x-text="ev.title"></span>
                                <span class="text-[10px] font-mono text-[#9E9993] shrink-0" x-text="ev.time_ago"></span>
                            </div>
                            <div class="text-[11px] font-semibold text-[#E87524] mt-0.5" x-text="ev.subtitle"></div>
                            <p class="text-[11px] text-[#9E9993] mt-1 line-clamp-2" x-text="ev.detail"></p>
                        </a>
                    </template>

                    <template x-if="!liveActivity || liveActivity.length === 0">
                        <div class="py-12 text-center text-xs text-[#9E9993]">
                            No recent incident status transitions or responder milestones logged yet.
                        </div>
                    </template>
                </div>
            </div>
        </section>

        {{-- D. INTERACTIVE ANIMATED CHART SUITE WITH REAL-TIME FILTERS --}}
        <section class="alerto-reveal-panel eoc-reveal-panel eoc-card p-5 sm:p-6 space-y-5" aria-label="Operational Analytics Visualizations">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-white/10 pb-4">
                <div>
                    <h2 class="text-sm sm:text-base font-black uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="bar-chart-3" class="w-4 h-4 text-[#E87524]"></i>
                        <span>Command Analytics &amp; Operational Intelligence</span>
                    </h2>
                    <p class="text-xs text-[#9E9993] mt-0.5">Live MariaDB queries across categories, barangays, monthly trends, and agency response activity.</p>
                </div>

                {{-- Interactive Filter Controls (Animated Chart Update without Page Refresh) --}}
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <input type="date" x-model="chartFilters.date_from" x-on:change="refreshTelemetry()"
                           class="px-2.5 py-1.5 rounded-xl border text-xs" title="Date From">
                    <input type="date" x-model="chartFilters.date_to" x-on:change="refreshTelemetry()"
                           class="px-2.5 py-1.5 rounded-xl border text-xs" title="Date To">
                    <select x-model="chartFilters.category_id" x-on:change="refreshTelemetry()" class="px-2.5 py-1.5 rounded-xl border text-xs">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <select x-model="chartFilters.barangay_id" x-on:change="refreshTelemetry()" class="px-2.5 py-1.5 rounded-xl border text-xs">
                        <option value="">All Barangays</option>
                        @foreach($barangays as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" x-on:click="resetChartFilters()"
                            class="px-3 py-1.5 rounded-xl border border-white/15 hover:border-[#E87524] text-xs font-bold transition-colors">
                        Reset
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div class="p-4 eoc-chart-well space-y-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#9E9993]">Monthly Incident Trends</h3>
                    <div class="h-56"><canvas id="chart-monthly-trends"></canvas></div>
                </div>

                <div class="p-4 eoc-chart-well space-y-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#9E9993]">Incidents by Category</h3>
                    <div class="h-56"><canvas id="chart-by-category"></canvas></div>
                </div>

                <div class="p-4 eoc-chart-well space-y-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#9E9993]">Incidents by Barangay</h3>
                    <div class="h-56"><canvas id="chart-by-barangay"></canvas></div>
                </div>

                <div class="p-4 eoc-chart-well space-y-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#9E9993]">Incidents by Status</h3>
                    <div class="h-56"><canvas id="chart-by-status"></canvas></div>
                </div>

                <div class="p-4 eoc-chart-well space-y-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#9E9993]">Office Agency Activity</h3>
                    <div class="h-56"><canvas id="chart-office-activity"></canvas></div>
                </div>

                <div class="p-4 eoc-chart-well space-y-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#9E9993]">Personnel Dispatch Activity</h3>
                    <div class="h-56"><canvas id="chart-personnel-activity"></canvas></div>
                </div>
            </div>
        </section>

        {{-- E. EOC LIVE INCIDENT QUEUE TABLE --}}
        <section class="alerto-reveal-panel eoc-reveal-panel">
            <x-table :headers="['Reference', 'Classification', 'Location', 'Priority', 'Assigned Agency', 'Submitted At', 'Status', 'Action']">
                <x-slot:toolbar>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-extrabold">EOC Live Incident Queue</span>
                        <x-badge variant="orange" size="sm">Realtime Live DB</x-badge>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.reports.index') }}" class="px-3 py-1.5 rounded-lg border border-black/15 dark:border-white/15 text-xs font-bold hover:border-[#E87524] transition-colors">
                            Full Reports
                        </a>
                        <a href="{{ route('admin.incidents.index') }}" class="alerto-btn-press px-3 py-1.5 rounded-lg bg-[#E87524] text-white text-xs font-bold hover:bg-[#B94F12] transition-colors flex items-center gap-1.5">
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            <span>View All Incidents</span>
                        </a>
                    </div>
                </x-slot:toolbar>

                @forelse($recentIncidents as $inc)
                    <tr class="hover:bg-[#FFF0E4]/40 dark:hover:bg-white/5 transition-colors">
                        <td class="px-6 py-4 font-mono font-bold text-xs text-[#E87524]">
                            <a href="{{ route('admin.incidents.show', $inc->reference_number) }}" class="hover:underline">
                                {{ $inc->reference_number }}
                            </a>
                        </td>
                        <td class="px-6 py-4 font-semibold">{{ $inc->category?->name ?? 'Emergency' }}</td>
                        <td class="px-6 py-4 text-xs">{{ $inc->barangay?->name ?? 'Hinoba-an' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md {{ $inc->priority === 'critical' ? 'bg-[#C83F43] text-white' : ($inc->priority === 'high' ? 'bg-[#E87524]/20 text-[#E87524]' : 'bg-[#397BA8]/20 text-[#397BA8]') }}">
                                {{ strtoupper($inc->priority) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-xs font-semibold">
                            {{ $inc->activeAssignments->first()?->office?->name ?? 'Unassigned' }}
                        </td>
                        <td class="px-6 py-4 text-xs font-mono text-[#9E9993]">{{ $inc->submitted_at?->diffForHumans() }}</td>
                        <td class="px-6 py-4">
                            <x-badge :variant="$inc->status === 'pending_review' ? 'warning' : ($inc->status === 'dispatched' ? 'emergency' : ($inc->status === 'resolved' ? 'success' : 'orange'))" size="sm" :pulse="$inc->status === 'pending_review'">
                                {{ strtoupper(str_replace('_', ' ', $inc->status)) }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.incidents.show', $inc->reference_number) }}" class="text-xs font-bold text-[#E87524] hover:underline">
                                Review &rarr;
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-[#9E9993] text-xs">
                            No active incidents recorded in the database yet.
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </section>
    </div>

    <script>
        function adminCommandCenter(initial) {
            return {
                stats: initial.stats || {},
                presence: initial.presence || {},
                charts: initial.charts || {},
                liveActivity: initial.liveActivity || [],
                refreshing: false,
                globeMode: 'webgl',
                pollTimer: null,
                chartFilters: {
                    date_from: '',
                    date_to: '',
                    category_id: '',
                    barangay_id: '',
                },

                async initCommandCenter() {
                    this.$nextTick(async () => {
                        if (window.AlertoEoc) {
                            window.AlertoEoc.dashboard.init(document);
                            window.AlertoEoc.stats.animateAllCounters(document);
                            await window.AlertoEoc.charts.renderDashboardCharts(this.charts);

                            const globeCanvas = document.getElementById('eoc-globe-canvas');
                            if (globeCanvas) {
                                const res = await window.AlertoEoc.globe.mount(globeCanvas, {
                                    criticalCount: this.stats.critical_count || 0,
                                });
                                this.globeMode = res.mode || 'webgl';
                            }
                        }
                    });

                    // Real-time background telemetry sync every 25s
                    this.pollTimer = setInterval(() => this.refreshTelemetry(true), 25000);
                },

                async refreshTelemetry(silent = false) {
                    if (!silent) this.refreshing = true;
                    try {
                        const params = new URLSearchParams();
                        Object.entries(this.chartFilters).forEach(([k, v]) => {
                            if (v) params.append(k, v);
                        });

                        const res = await fetch('{{ route('admin.dashboard.telemetry') }}?' + params.toString(), {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (res.ok) {
                            const payload = await res.json();
                            if (payload.telemetry) {
                                this.stats = payload.telemetry.stats || this.stats;
                                this.presence = payload.telemetry.presence || this.presence;
                                this.charts = payload.telemetry.charts || this.charts;
                                this.liveActivity = payload.telemetry.live_activity || this.liveActivity;
                                if (window.AlertoEoc?.charts) {
                                    window.AlertoEoc.charts.updateAllCharts(this.charts);
                                }
                            }
                        }
                    } catch (e) {
                        console.error('Telemetry refresh failed:', e);
                    } finally {
                        if (!silent) this.refreshing = false;
                    }
                },

                resetChartFilters() {
                    this.chartFilters = { date_from: '', date_to: '', category_id: '', barangay_id: '' };
                    this.refreshTelemetry();
                }
            };
        }
    </script>
</x-layouts.admin>
