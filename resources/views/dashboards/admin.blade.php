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
        <section class="eoc-hero-section relative rounded-[24px] overflow-hidden border border-[#3187D8]/30 bg-gradient-to-br from-[#0B1726] via-[#101E2E] to-[#173957] text-[#EDF4FC] shadow-2xl">
            {{-- Ambient Canvas Particle Field (FPS-throttled & paused when hidden) --}}
            <canvas id="eoc-ambient-particles" class="absolute inset-0 w-full h-full pointer-events-none opacity-80" aria-hidden="true"></canvas>

            {{-- Subtle Technical Grid & Scanline --}}
            <div class="absolute inset-0 eoc-tactical-grid pointer-events-none opacity-60" aria-hidden="true"></div>
            <div class="eoc-scanline absolute inset-x-0 h-20 bg-gradient-to-b from-transparent via-[#34B9D4]/10 to-transparent pointer-events-none" aria-hidden="true"></div>

            <div class="relative z-10 p-6 lg:p-8 grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                {{-- Left Brand & EOC Identity --}}
                <div class="lg:col-span-8 space-y-4">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#19865B]/20 border border-[#19865B]/45 text-[#6AE0B0] text-[11px] font-mono font-bold uppercase tracking-wider">
                            <span class="w-2 h-2 rounded-full bg-[#19865B] animate-ping"></span>
                            LIVE SYSTEM TELEMETRY · CONNECTED
                        </span>
                        <span class="px-3 py-1 rounded-full bg-white/10 border border-white/15 text-[#93A4B8] text-[11px] font-mono uppercase">
                            ALERTO EOC · Hinoba-an LDRRMO
                        </span>
                        @if(($stats['critical_count'] ?? 0) > 0)
                            <span class="px-3 py-1 rounded-full bg-[#DB4545]/25 border border-[#DB4545]/60 text-[#FDA4AF] text-[11px] font-mono font-black eoc-glow-critical">
                                ⚠ {{ $stats['critical_count'] }} CRITICAL ALERT ACTIVE
                            </span>
                        @endif
                    </div>

                    <div>
                        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black tracking-tight text-white">
                            DIAL DAPH Command Center
                        </h1>
                        <p class="text-xs sm:text-sm text-[#93A4B8] max-w-2xl mt-1.5 leading-relaxed">
                            Real-time municipal emergency receiving, call verification, SOS response, multi-agency coordination, and live telemetry for Hinoba-an, Negros Occidental.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5 pt-1">
                        <a href="{{ route('admin.incoming.index') }}"
                           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs shadow-lg transition-all">
                            <i data-lucide="phone-call" class="w-4 h-4"></i>
                            <span>Incoming Reports ({{ $stats['incoming_reports'] ?? 0 }})</span>
                        </a>
                        <a href="{{ route('admin.sos.index') }}"
                           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#DB4545] hover:bg-[#c53636] text-white font-black text-xs shadow-lg shadow-[#DB4545]/25 transition-all">
                            <i data-lucide="siren" class="w-4 h-4"></i>
                            <span>SOS Alerts ({{ $stats['active_sos'] ?? 0 }})</span>
                        </a>
                        <a href="{{ route('admin.dispatch.index') }}"
                           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#3187D8] hover:bg-[#2671ba] text-white font-bold text-xs shadow-md transition-all">
                            <i data-lucide="send" class="w-4 h-4"></i>
                            <span>Dispatch Console</span>
                        </a>
                        <a href="{{ route('admin.map') }}"
                           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 border border-white/20 text-[#EDF4FC] font-bold text-xs transition-all">
                            <i data-lucide="map-pin" class="w-4 h-4 text-[#34B9D4]"></i>
                            <span>Live Operations Map</span>
                        </a>
                    </div>
                </div>

                {{-- Right: Interactive 3D Three.js WebGL Operations Telemetry Sphere (Decorative HUD) --}}
                <div class="lg:col-span-4 flex flex-col items-center justify-center">
                    <div class="relative w-52 h-52 sm:w-56 sm:h-56 rounded-full bg-[#0B1726]/70 border border-[#34B9D4]/30 flex items-center justify-center shadow-inner eoc-glow-info overflow-hidden"
                         title="Interactive 3D Telemetry HUD — Drag to rotate. Actual geographic pins are mapped in Live Operations Map.">
                        <canvas id="eoc-globe-canvas" class="w-full h-full cursor-grab active:cursor-grabbing"></canvas>
                        <div class="absolute bottom-2.5 px-2.5 py-0.5 rounded-full bg-[#0B1726]/85 border border-white/15 text-[9px] font-mono text-[#34B9D4] tracking-wider pointer-events-none">
                            3D EOC TELEMETRY HUD · <span x-text="globeMode === 'webgl' ? 'WEBGL' : '2D RADAR'">WEBGL</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- B-PRE. URGENT SOS & INCOMING UNCLASSIFIED REPORTS PANELS --}}
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <div class="eoc-card p-5 border border-rose-500/35 space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-black uppercase tracking-wider text-rose-400 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-ping"></span>
                        Active SOS Alerts Queue ({{ $stats['active_sos'] ?? 0 }})
                    </h2>
                    <a href="{{ route('admin.sos.index') }}" class="text-xs font-bold text-[#34B9D4] hover:underline">Open SOS Console &rarr;</a>
                </div>
                @forelse(($activeSosAlerts ?? collect()) as $sos)
                    <a href="{{ route('admin.sos.show', $sos->id) }}" class="block p-3 rounded-xl bg-rose-950/35 hover:bg-rose-950/60 border border-rose-500/30 transition-all text-xs">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono font-black text-white">{{ $sos->reference_number }} · {{ strtoupper($sos->sender_type) }} SOS</span>
                            <span class="px-2 py-0.5 rounded bg-rose-600 text-white text-[10px] font-bold uppercase">{{ str_replace('_', ' ', $sos->status) }}</span>
                        </div>
                        <p class="text-slate-300 mt-1">{{ $sos->sender_name }} ({{ $sos->sender_phone ?? 'no phone' }}) · {{ $sos->barangay?->name ?? 'Unverified Location' }}</p>
                    </a>
                @empty
                    <p class="text-xs text-slate-400 py-4 text-center">No active SOS emergency alerts at this moment.</p>
                @endforelse
            </div>

            <div class="eoc-card p-5 border border-amber-500/35 space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-black uppercase tracking-wider text-amber-400 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                        Incoming Unclassified Reports ({{ $stats['incoming_reports'] ?? 0 }})
                    </h2>
                    <a href="{{ route('admin.incoming.index') }}" class="text-xs font-bold text-[#34B9D4] hover:underline">Open Call Queue &rarr;</a>
                </div>
                @forelse(($incomingReports ?? collect()) as $rep)
                    <a href="{{ route('admin.incidents.show', $rep->reference_number) }}" class="block p-3 rounded-xl bg-amber-950/25 hover:bg-amber-950/45 border border-amber-500/30 transition-all text-xs">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono font-black text-white">{{ $rep->reference_number }}</span>
                            <span class="px-2 py-0.5 rounded bg-amber-500 text-slate-950 text-[10px] font-bold uppercase">{{ str_replace('_', ' ', $rep->workflow_stage ?? 'new_report') }}</span>
                        </div>
                        <p class="text-slate-300 mt-1">{{ $rep->caller_name }} · {{ $rep->caller_phone }} · {{ $rep->barangay?->name ?? 'GPS Pin Pending Verification' }}</p>
                    </a>
                @empty
                    <p class="text-xs text-slate-400 py-4 text-center">All incoming reports have been called and classified.</p>
                @endforelse
            </div>
        </section>

        {{-- B. ADVANCED 10-METRIC REAL-TIME DATABASE STATISTICS GRID --}}
        <section aria-label="Operational Statistics">
            {{-- 7 Core Incident Lifecycle Metrics --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3.5">
                {{-- 1. Total Incidents --}}
                <div class="eoc-kpi-card eoc-card p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Incidents</span>
                        <span class="w-8 h-8 rounded-xl bg-[#173957]/40 text-[#34B9D4] flex items-center justify-center">
                            <i data-lucide="layers" class="w-4 h-4"></i>
                        </span>
                    </div>
                    <div class="mt-2">
                        <p class="text-2xl sm:text-3xl font-black font-mono" data-eoc-count="{{ $stats['total'] ?? 0 }}" x-text="stats.total">{{ $stats['total'] ?? 0 }}</p>
                        @if(($stats['trend_percent'] ?? null) !== null)
                            <span class="text-[10px] font-mono {{ $stats['trend_percent'] >= 0 ? 'text-[#F0A82E]' : 'text-[#19865B]' }}">
                                {{ $stats['trend_percent'] >= 0 ? '+' : '' }}{{ $stats['trend_percent'] }}% vs prior 7d
                            </span>
                        @else
                            <span class="text-[10px] text-slate-400 font-mono">All records</span>
                        @endif
                    </div>
                </div>

                {{-- 2. Pending Reports --}}
                <div class="eoc-kpi-card eoc-card p-4 flex flex-col justify-between border-[#F0A82E]/35">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#F0A82E]">Pending</span>
                        <span class="w-8 h-8 rounded-xl bg-[#F0A82E]/15 text-[#F0A82E] flex items-center justify-center">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                        </span>
                    </div>
                    <div class="mt-2">
                        <p class="text-2xl sm:text-3xl font-black font-mono text-[#F0A82E]" data-eoc-count="{{ $stats['pending_review'] ?? 0 }}" x-text="stats.pending_review">{{ $stats['pending_review'] ?? 0 }}</p>
                        <span class="text-[10px] text-slate-400 font-mono">Needs intake review</span>
                    </div>
                </div>

                {{-- 3. Accepted Incidents --}}
                <div class="eoc-kpi-card eoc-card p-4 flex flex-col justify-between border-[#3187D8]/35">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#3187D8]">Accepted</span>
                        <span class="w-8 h-8 rounded-xl bg-[#3187D8]/15 text-[#3187D8] flex items-center justify-center">
                            <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                        </span>
                    </div>
                    <div class="mt-2">
                        <p class="text-2xl sm:text-3xl font-black font-mono text-[#3187D8]" data-eoc-count="{{ $stats['accepted'] ?? 0 }}" x-text="stats.accepted">{{ $stats['accepted'] ?? 0 }}</p>
                        <span class="text-[10px] text-slate-400 font-mono">Verified queue</span>
                    </div>
                </div>

                {{-- 4. Dispatched Incidents --}}
                <div class="eoc-kpi-card eoc-card p-4 flex flex-col justify-between border-[#34B9D4]/35">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#34B9D4]">Dispatched</span>
                        <span class="w-8 h-8 rounded-xl bg-[#34B9D4]/15 text-[#34B9D4] flex items-center justify-center">
                            <i data-lucide="send" class="w-4 h-4"></i>
                        </span>
                    </div>
                    <div class="mt-2">
                        <p class="text-2xl sm:text-3xl font-black font-mono text-[#34B9D4]" data-eoc-count="{{ $stats['dispatched'] ?? 0 }}" x-text="stats.dispatched">{{ $stats['dispatched'] ?? 0 }}</p>
                        <span class="text-[10px] text-slate-400 font-mono">Units mobilized</span>
                    </div>
                </div>

                {{-- 5. Active Incidents --}}
                <div class="eoc-kpi-card eoc-card p-4 flex flex-col justify-between {{ ($stats['critical_count'] ?? 0) > 0 ? 'eoc-glow-critical border-[#DB4545]/50' : '' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#DB4545]">Active Field</span>
                        <span class="w-8 h-8 rounded-xl bg-[#DB4545]/15 text-[#DB4545] flex items-center justify-center">
                            <i data-lucide="activity" class="w-4 h-4"></i>
                        </span>
                    </div>
                    <div class="mt-2">
                        <p class="text-2xl sm:text-3xl font-black font-mono text-[#DB4545]" data-eoc-count="{{ $stats['active_incidents'] ?? 0 }}" x-text="stats.active_incidents">{{ $stats['active_incidents'] ?? 0 }}</p>
                        <span class="text-[10px] text-slate-400 font-mono">On scene / en route</span>
                    </div>
                </div>

                {{-- 6. Resolved Incidents --}}
                <div class="eoc-kpi-card eoc-card p-4 flex flex-col justify-between border-[#19865B]/35">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#19865B]">Resolved</span>
                        <span class="w-8 h-8 rounded-xl bg-[#19865B]/15 text-[#19865B] flex items-center justify-center">
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                        </span>
                    </div>
                    <div class="mt-2">
                        <p class="text-2xl sm:text-3xl font-black font-mono text-[#19865B]" data-eoc-count="{{ $stats['resolved'] ?? 0 }}" x-text="stats.resolved">{{ $stats['resolved'] ?? 0 }}</p>
                        <span class="text-[10px] text-slate-400 font-mono">Cleared &amp; closed</span>
                    </div>
                </div>

                {{-- 7. Rejected Incidents --}}
                <div class="eoc-kpi-card eoc-card p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Rejected</span>
                        <span class="w-8 h-8 rounded-xl bg-slate-500/15 text-slate-400 flex items-center justify-center">
                            <i data-lucide="x-circle" class="w-4 h-4"></i>
                        </span>
                    </div>
                    <div class="mt-2">
                        <p class="text-2xl sm:text-3xl font-black font-mono" data-eoc-count="{{ $stats['rejected'] ?? 0 }}" x-text="stats.rejected">{{ $stats['rejected'] ?? 0 }}</p>
                        <span class="text-[10px] text-slate-400 font-mono">Declined reports</span>
                    </div>
                </div>
            </div>

            {{-- 3 Account Presence Metrics + Live Performance Readout --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 mt-3.5">
                <a href="{{ route('admin.monitor.users', ['account_type' => 'personnel', 'status' => 'online']) }}"
                   class="eoc-kpi-card eoc-card p-4 flex items-center justify-between hover:scale-[1.01]">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Personnel Online</span>
                        <span class="text-2xl font-black font-mono text-[#19865B] mt-1 block" data-eoc-count="{{ $presence['personnel_online'] ?? 0 }}" x-text="presence.personnel_online">{{ $presence['personnel_online'] ?? 0 }}</span>
                        <span class="text-[10px] text-slate-400 font-mono">Active account session</span>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-[#19865B]/15 text-[#19865B] flex items-center justify-center">
                        <i data-lucide="radio" class="w-5 h-5"></i>
                    </div>
                </a>

                <a href="{{ route('admin.monitor.users', ['account_type' => 'office', 'status' => 'online']) }}"
                   class="eoc-kpi-card eoc-card p-4 flex items-center justify-between hover:scale-[1.01]">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Offices Online</span>
                        <span class="text-2xl font-black font-mono text-[#3187D8] mt-1 block" data-eoc-count="{{ $presence['offices_online'] ?? 0 }}" x-text="presence.offices_online">{{ $presence['offices_online'] ?? 0 }}</span>
                        <span class="text-[10px] text-slate-400 font-mono">Agency desk consoles</span>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-[#3187D8]/15 text-[#3187D8] flex items-center justify-center">
                        <i data-lucide="building-2" class="w-5 h-5"></i>
                    </div>
                </a>

                <a href="{{ route('admin.monitor.users', ['account_type' => 'citizen', 'status' => 'online']) }}"
                   class="eoc-kpi-card eoc-card p-4 flex items-center justify-between hover:scale-[1.01]">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Citizens Online</span>
                        <span class="text-2xl font-black font-mono text-[#34B9D4] mt-1 block" data-eoc-count="{{ $presence['citizens_online'] ?? 0 }}" x-text="presence.citizens_online">{{ $presence['citizens_online'] ?? 0 }}</span>
                        <span class="text-[10px] text-slate-400 font-mono">Presence only · No GPS tracked</span>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-[#34B9D4]/15 text-[#34B9D4] flex items-center justify-center">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                </a>

                <div class="eoc-kpi-card eoc-card p-4 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Avg Triage / Resolution</span>
                        <div class="text-lg font-black font-mono mt-1">
                            <span class="text-[#34B9D4]" x-text="(charts.response_performance?.avg_accept_minutes || 0) + 'm'">0m</span>
                            <span class="text-slate-500 text-xs">/</span>
                            <span class="text-[#19865B]" x-text="(charts.response_performance?.avg_resolve_minutes || 0) + 'm'">0m</span>
                        </div>
                        <span class="text-[10px] text-slate-400 font-mono">Measured from DB timestamps</span>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-[#3187D8]/15 text-[#34B9D4] flex items-center justify-center">
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
                <div class="eoc-reveal-panel eoc-card p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-400/15 pb-3">
                        <div class="flex items-center gap-2">
                            <i data-lucide="siren" class="w-4 h-4 text-[#DB4545]"></i>
                            <h2 class="text-sm font-black uppercase tracking-wider">Priority Emergency Queue</h2>
                        </div>
                        <a href="{{ route('admin.incidents.index', ['priority' => 'critical']) }}" class="text-xs font-bold text-[#34B9D4] hover:underline">
                            Open Queue &rarr;
                        </a>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @forelse($highPriorityIncidents as $hp)
                            <a href="{{ route('admin.incidents.show', $hp->reference_number) }}"
                               class="p-3.5 rounded-2xl border transition-all flex flex-col justify-between gap-2 {{ $hp->priority === 'critical' ? 'border-[#DB4545]/50 bg-[#DB4545]/10 hover:bg-[#DB4545]/15' : 'border-[#F0A82E]/35 bg-[#F0A82E]/5 hover:bg-[#F0A82E]/10' }}">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-mono text-xs font-black">{{ $hp->reference_number }}</span>
                                    <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase {{ $hp->priority === 'critical' ? 'bg-[#DB4545] text-white' : 'bg-[#F0A82E] text-[#0B1726]' }}">
                                        {{ $hp->priority }}
                                    </span>
                                </div>
                                <div class="text-xs font-bold truncate">{{ $hp->category?->name ?? 'Emergency Incident' }}</div>
                                <div class="flex items-center justify-between text-[10px] text-slate-400 font-mono">
                                    <span>{{ $hp->barangay?->name ?? 'Hinoba-an' }}</span>
                                    <span>{{ strtoupper(str_replace('_', ' ', $hp->status)) }}</span>
                                </div>
                            </a>
                        @empty
                            <div class="col-span-full py-6 text-center text-xs text-slate-400">
                                No active critical or high-priority incidents awaiting action.
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Interactive Live GIS Portal Gateway --}}
                <x-map-container height="h-[320px]" title="Hinoba-an LDRRMO Operations GIS Grid">
                    <div class="absolute bottom-4 right-4 z-10 p-3 rounded-2xl bg-[#0B1726]/90 border border-[#34B9D4]/30 text-white backdrop-blur-md shadow-xl text-xs space-y-2">
                        <div class="font-bold text-[#19865B] flex items-center gap-1.5">
                            <i data-lucide="radio" class="w-3.5 h-3.5"></i>
                            <span>Live Map Ready</span>
                        </div>
                        <p class="text-[11px] text-[#93A4B8] font-mono">
                            Active Responders Online: <strong class="text-white" x-text="presence.personnel_online">{{ $presence['personnel_online'] ?? 0 }}</strong>
                        </p>
                        <a href="{{ route('admin.map') }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#3187D8] hover:bg-[#34B9D4] hover:text-[#0B1726] text-white font-bold text-[11px] transition-colors">
                            <span>Launch Full Operations Map</span>
                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </x-map-container>
            </div>

            {{-- Real Database Live Incident & Responder Activity Feed (5 cols) --}}
            <div class="lg:col-span-5 eoc-reveal-panel eoc-card p-5 flex flex-col">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-400/15">
                    <h2 class="text-sm font-black uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="activity" class="w-4 h-4 text-[#34B9D4]"></i>
                        <span>Live EOC Activity Stream</span>
                    </h2>
                    <button type="button" x-on:click="refreshTelemetry()"
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[#3187D8]/15 text-[#34B9D4] hover:bg-[#3187D8]/25 text-[10px] font-mono font-bold">
                        <i data-lucide="refresh-cw" class="w-3 h-3" :class="refreshing ? 'animate-spin' : ''"></i>
                        <span x-text="refreshing ? 'SYNCING' : 'REALTIME DB'">REALTIME DB</span>
                    </button>
                </div>

                <div class="space-y-3 overflow-y-auto alerto-scrollbar flex-1 max-h-[500px] pr-1">
                    <template x-for="ev in liveActivity" :key="ev.id">
                        <a :href="ev.url"
                           class="block p-3.5 rounded-2xl border border-slate-400/15 hover:border-[#34B9D4]/45 transition-all"
                           :class="{
                               'bg-[#DB4545]/10 border-[#DB4545]/35': ev.priority === 'critical',
                               'bg-[#3187D8]/10': ev.kind === 'dispatch',
                               'bg-[#19865B]/10': ev.kind === 'personnel'
                           }">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-mono font-bold text-xs" x-text="ev.title"></span>
                                <span class="text-[10px] font-mono text-slate-400 shrink-0" x-text="ev.time_ago"></span>
                            </div>
                            <div class="text-[11px] font-semibold text-[#34B9D4] mt-0.5" x-text="ev.subtitle"></div>
                            <p class="text-[11px] text-slate-400 mt-1 line-clamp-2" x-text="ev.detail"></p>
                        </a>
                    </template>

                    <template x-if="!liveActivity || liveActivity.length === 0">
                        <div class="py-12 text-center text-xs text-slate-400">
                            No recent incident status transitions or responder milestones logged yet.
                        </div>
                    </template>
                </div>
            </div>
        </section>

        {{-- D. INTERACTIVE ANIMATED CHART SUITE WITH REAL-TIME FILTERS --}}
        <section class="eoc-reveal-panel eoc-card p-5 sm:p-6 space-y-5" aria-label="Operational Analytics Visualizations">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-400/15 pb-4">
                <div>
                    <h2 class="text-sm sm:text-base font-black uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="bar-chart-3" class="w-4 h-4 text-[#34B9D4]"></i>
                        <span>Command Analytics &amp; Operational Intelligence</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Live MariaDB queries across categories, barangays, monthly trends, and agency response activity.</p>
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
                            class="px-3 py-1.5 rounded-xl border border-slate-400/25 hover:border-[#34B9D4] text-xs font-bold">
                        Reset
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div class="p-4 eoc-chart-well space-y-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Monthly Incident Trends</h3>
                    <div class="h-56"><canvas id="chart-monthly-trends"></canvas></div>
                </div>

                <div class="p-4 eoc-chart-well space-y-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Incidents by Category</h3>
                    <div class="h-56"><canvas id="chart-by-category"></canvas></div>
                </div>

                <div class="p-4 eoc-chart-well space-y-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Incidents by Barangay</h3>
                    <div class="h-56"><canvas id="chart-by-barangay"></canvas></div>
                </div>

                <div class="p-4 eoc-chart-well space-y-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Incidents by Status</h3>
                    <div class="h-56"><canvas id="chart-by-status"></canvas></div>
                </div>

                <div class="p-4 eoc-chart-well space-y-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Office Agency Activity</h3>
                    <div class="h-56"><canvas id="chart-office-activity"></canvas></div>
                </div>

                <div class="p-4 eoc-chart-well space-y-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Personnel Dispatch Activity</h3>
                    <div class="h-56"><canvas id="chart-personnel-activity"></canvas></div>
                </div>
            </div>
        </section>

        {{-- E. EOC LIVE INCIDENT QUEUE TABLE --}}
        <section class="eoc-reveal-panel">
            <x-table :headers="['Reference', 'Classification', 'Location', 'Priority', 'Assigned Agency', 'Submitted At', 'Status', 'Action']">
                <x-slot:toolbar>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-extrabold">EOC Live Incident Queue</span>
                        <x-badge variant="navy" size="sm">Realtime Live DB</x-badge>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.reports.index') }}" class="px-3 py-1.5 rounded-lg border border-slate-400/30 text-xs font-bold hover:border-[#34B9D4] transition-colors">
                            Full Reports
                        </a>
                        <a href="{{ route('admin.incidents.index') }}" class="px-3 py-1.5 rounded-lg bg-[#3187D8] text-white text-xs font-bold hover:bg-[#2770b6] transition-colors flex items-center gap-1.5">
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            <span>View All Incidents</span>
                        </a>
                    </div>
                </x-slot:toolbar>

                @forelse($recentIncidents as $inc)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="px-6 py-4 font-mono font-bold text-xs text-[#34B9D4]">
                            <a href="{{ route('admin.incidents.show', $inc->reference_number) }}" class="hover:underline">
                                {{ $inc->reference_number }}
                            </a>
                        </td>
                        <td class="px-6 py-4 font-semibold">{{ $inc->category?->name ?? 'Emergency' }}</td>
                        <td class="px-6 py-4 text-xs">{{ $inc->barangay?->name ?? 'Hinoba-an' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md {{ $inc->priority === 'critical' ? 'bg-[#DB4545] text-white' : ($inc->priority === 'high' ? 'bg-[#F0A82E]/20 text-[#F0A82E]' : 'bg-[#3187D8]/20 text-[#34B9D4]') }}">
                                {{ strtoupper($inc->priority) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-xs font-semibold">
                            {{ $inc->activeAssignments->first()?->office?->name ?? 'Unassigned' }}
                        </td>
                        <td class="px-6 py-4 text-xs font-mono text-slate-400">{{ $inc->submitted_at?->diffForHumans() }}</td>
                        <td class="px-6 py-4">
                            <x-badge :variant="$inc->status === 'pending_review' ? 'warning' : ($inc->status === 'dispatched' ? 'emergency' : ($inc->status === 'resolved' ? 'success' : 'info'))" size="sm" :pulse="$inc->status === 'pending_review'">
                                {{ strtoupper(str_replace('_', ' ', $inc->status)) }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.incidents.show', $inc->reference_number) }}" class="text-xs font-bold text-[#34B9D4] hover:underline">
                                Review &rarr;
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-slate-400 text-xs">
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
