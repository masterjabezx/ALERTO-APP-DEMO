<x-layouts.super-admin :title="'Executive Dashboard — LDRRMO Super Admin'">
    @php
        $stats = $telemetry['stats'] ?? [];
        $presence = $telemetry['presence'] ?? [];
        $incomingReports = $telemetry['incoming_reports'] ?? collect();
        $activeSosAlerts = $telemetry['active_sos_alerts'] ?? collect();
        $liveActivity = $telemetry['live_activity'] ?? [];
    @endphp

    <div class="space-y-6">
        {{-- Executive Hero Banner --}}
        <section class="alerto-hero-section relative overflow-hidden rounded-[24px] p-6 sm:p-8 bg-gradient-to-br from-[#101214] via-[#17191C] to-[#292D32] border border-[#E87524]/30 shadow-2xl text-[#F5F3EF]">
            <canvas id="alerto-ambient-particles" class="absolute inset-0 w-full h-full pointer-events-none opacity-75" aria-hidden="true"></canvas>
            <div class="absolute inset-0 alerto-grid-canvas opacity-35 pointer-events-none" aria-hidden="true"></div>

            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="space-y-2.5">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#E87524]/18 border border-[#E87524]/45 text-[#F29A56] text-[11px] font-mono font-bold uppercase tracking-wider badge-fixed">
                        <span class="w-2 h-2 rounded-full bg-[#E87524] animate-ping"></span>
                        LDRRMO SUPER ADMIN · HINOBA-AN MUNICIPAL GOVERNANCE
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                        Executive Command &amp; System Oversight
                    </h1>
                    <p class="text-xs sm:text-sm text-[#D4D0C9] max-w-2xl leading-relaxed">
                        Command authority over DIAL DAPH Command Center operations, automated emergency &amp; SOS routing policies, admin account governance, and cryptographic backups.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2.5 text-xs shrink-0">
                    <a href="{{ route('super-admin.admins.index') }}" class="px-4 py-2.5 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white font-bold shadow-lg shadow-[#E87524]/25 transition-colors btn-fixed">
                        Manage Admin Accounts
                    </a>
                    <a href="{{ route('super-admin.routing.index') }}" class="px-4 py-2.5 rounded-xl bg-[#292D32] hover:bg-[#454A50] border border-white/15 text-white font-bold transition-colors btn-fixed">
                        Routing &amp; Categories
                    </a>
                    <a href="{{ route('super-admin.backups.index') }}" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 border border-white/15 text-[#F5F3EF] font-bold transition-colors btn-fixed">
                        Backup &amp; Restore
                    </a>
                </div>
            </div>
        </section>

        {{-- Operational & Governance KPI Strip --}}
        <section class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 text-xs">
            <a href="{{ route('super-admin.incoming.index') }}" class="alerto-kpi-card eoc-card p-4 border-t-2 border-t-[#E87524] alerto-interactive-card block">
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#E87524] block">Incoming Reports</span>
                <p class="text-2xl font-black font-mono mt-1.5" data-eoc-count="{{ (int) ($stats['incoming_reports'] ?? 0) }}">{{ number_format((int) ($stats['incoming_reports'] ?? 0)) }}</p>
                <span class="text-[10px] text-[#9D9993]">Awaiting DIAL DAPH call</span>
            </a>

            <a href="{{ route('super-admin.sos.index') }}" class="alerto-kpi-card eoc-card p-4 border-t-2 border-t-[#C83F43] alerto-interactive-card block">
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#C83F43] block">Active SOS</span>
                <p class="text-2xl font-black font-mono mt-1.5 text-[#C83F43]" data-eoc-count="{{ (int) ($stats['active_sos'] ?? 0) }}">{{ number_format((int) ($stats['active_sos'] ?? 0)) }}</p>
                <span class="text-[10px] text-[#9D9993]">Citizen, Guest &amp; Responder</span>
            </a>

            <a href="{{ route('super-admin.incidents.index') }}" class="alerto-kpi-card eoc-card p-4 border-t-2 border-t-[#27845B] alerto-interactive-card block">
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#27845B] block">Active Responses</span>
                <p class="text-2xl font-black font-mono mt-1.5" data-eoc-count="{{ (int) (($stats['dispatched'] ?? 0) + ($stats['active_incidents'] ?? 0)) }}">{{ number_format((int) (($stats['dispatched'] ?? 0) + ($stats['active_incidents'] ?? 0))) }}</p>
                <span class="text-[10px] text-[#9D9993]">{{ number_format((int) ($stats['critical_count'] ?? 0)) }} critical</span>
            </a>

            <a href="{{ route('super-admin.admins.index') }}" class="alerto-kpi-card eoc-card p-4 border-t-2 border-t-[#397BA8] alerto-interactive-card block">
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#397BA8] block">Command Admins</span>
                <p class="text-2xl font-black font-mono mt-1.5" data-eoc-count="{{ $adminsCount }}">{{ number_format($adminsCount) }}</p>
                <span class="text-[10px] text-[#9D9993]">{{ $superAdminsCount }} Super Admin(s)</span>
            </a>

            <a href="{{ route('super-admin.routing.index') }}" class="alerto-kpi-card eoc-card p-4 border-t-2 border-t-[#E87524] alerto-interactive-card block">
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#E87524] block">Active Routing Rules</span>
                <p class="text-2xl font-black font-mono mt-1.5" data-eoc-count="{{ $routingRulesCount + $sosRulesCount }}">{{ number_format($routingRulesCount + $sosRulesCount) }}</p>
                <span class="text-[10px] text-[#9D9993]">{{ $routingRulesCount }} Incident · {{ $sosRulesCount }} SOS</span>
            </a>

            <a href="{{ route('super-admin.backups.index') }}" class="alerto-kpi-card eoc-card p-4 border-t-2 border-t-[#77736E] alerto-interactive-card block">
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#9D9993] block">Latest Backup</span>
                <p class="text-sm font-black font-mono mt-2 truncate">{{ $latestBackup?->created_at?->diffForHumans() ?? 'None yet' }}</p>
                <span class="text-[10px] text-[#9D9993] truncate block">{{ $latestBackup?->backup_name ?? 'Create initial snapshot' }}</span>
            </a>
        </section>

        {{-- Live Presence Strip --}}
        <section class="alerto-reveal-panel eoc-card p-5">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3.5">
                <h2 class="text-xs font-black uppercase tracking-wider text-[#E87524] flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#27845B] animate-pulse"></span>
                    <span>Live Account Presence Snapshot</span>
                </h2>
                <a href="{{ route('super-admin.monitoring') }}" class="text-xs font-bold text-[#E87524] hover:underline">Open System Monitoring &rarr;</a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 text-xs">
                <div class="p-3 rounded-xl bg-white/5 border border-white/5"><span class="text-[10px] text-[#9D9993] block">Admins Online</span><strong class="text-lg font-mono text-[#F29A56]">{{ (int) ($presence['admins_online'] ?? 0) }}</strong></div>
                <div class="p-3 rounded-xl bg-white/5 border border-white/5"><span class="text-[10px] text-[#9D9993] block">Offices Online</span><strong class="text-lg font-mono text-[#397BA8]">{{ (int) ($presence['offices_online'] ?? 0) }}</strong></div>
                <div class="p-3 rounded-xl bg-white/5 border border-white/5"><span class="text-[10px] text-[#9D9993] block">Personnel Online</span><strong class="text-lg font-mono text-[#27845B]">{{ (int) ($presence['personnel_online'] ?? 0) }}</strong></div>
                <div class="p-3 rounded-xl bg-white/5 border border-white/5"><span class="text-[10px] text-[#9D9993] block">Citizens Online</span><strong class="text-lg font-mono">{{ (int) ($presence['citizens_online'] ?? 0) }}</strong></div>
                <div class="p-3 rounded-xl bg-white/5 border border-white/5"><span class="text-[10px] text-[#9D9993] block">Idle</span><strong class="text-lg font-mono text-[#D99024]">{{ (int) ($presence['idle_accounts'] ?? 0) }}</strong></div>
                <div class="p-3 rounded-xl bg-white/5 border border-white/5"><span class="text-[10px] text-[#9D9993] block">Conn. Lost</span><strong class="text-lg font-mono text-[#C83F43]">{{ (int) ($presence['connection_lost_accounts'] ?? 0) }}</strong></div>
                <div class="p-3 rounded-xl bg-white/5 border border-white/5"><span class="text-[10px] text-[#9D9993] block">Offline</span><strong class="text-lg font-mono text-[#77736E]">{{ (int) ($presence['offline_accounts'] ?? 0) }}</strong></div>
            </div>
        </section>

        {{-- Live Queues & Activity Feed --}}
        <section class="alerto-reveal-panel grid grid-cols-1 lg:grid-cols-3 gap-5 text-xs">
            <div class="eoc-card p-5 border-t-2 border-t-[#C83F43] space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="font-black uppercase tracking-wider text-[#C83F43]">Active SOS Alerts</h2>
                    <a href="{{ route('super-admin.sos.index') }}" class="text-[#E87524] font-bold hover:underline">View All</a>
                </div>
                @forelse($activeSosAlerts as $sos)
                    <a href="{{ route('super-admin.sos.show', $sos->id) }}" class="block p-3.5 rounded-xl bg-[#C83F43]/10 border border-[#C83F43]/30 hover:border-[#C83F43]/60 transition-colors">
                        <div class="flex justify-between font-mono font-bold">
                            <span>{{ $sos->reference_number }}</span>
                            <span class="text-[#C83F43] uppercase">{{ $sos->sender_type }}</span>
                        </div>
                        <p class="text-[#9D9993] mt-1">{{ $sos->sender_name }} · {{ $sos->barangay?->name ?? 'Unverified Location' }}</p>
                    </a>
                @empty
                    <p class="text-[#9D9993] py-6 text-center">No active SOS alerts.</p>
                @endforelse
            </div>

            <div class="eoc-card p-5 border-t-2 border-t-[#E87524] space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="font-black uppercase tracking-wider text-[#E87524]">Incoming Reports</h2>
                    <a href="{{ route('super-admin.incoming.index') }}" class="text-[#E87524] font-bold hover:underline">View All</a>
                </div>
                @forelse($incomingReports as $rep)
                    <a href="{{ route('super-admin.incidents.show', $rep->reference_number) }}" class="block p-3.5 rounded-xl bg-[#E87524]/10 border border-[#E87524]/25 hover:border-[#E87524]/60 transition-colors">
                        <div class="flex justify-between font-mono font-bold">
                            <span>{{ $rep->reference_number }}</span>
                            <span class="text-[#E87524] uppercase">{{ str_replace('_', ' ', $rep->workflow_stage ?? 'new_report') }}</span>
                        </div>
                        <p class="text-[#9D9993] mt-1">{{ $rep->caller_name }} ({{ $rep->caller_phone }}) · {{ $rep->barangay?->name ?? 'Pin Pending' }}</p>
                    </a>
                @empty
                    <p class="text-[#9D9993] py-6 text-center">No unclassified incoming reports.</p>
                @endforelse
            </div>

            <div class="eoc-card p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="font-black uppercase tracking-wider text-[#9D9993]">Operational Timeline</h2>
                    <a href="{{ route('super-admin.audit.index') }}" class="text-[#E87524] font-bold hover:underline">Audit Logs</a>
                </div>
                <div class="space-y-2 max-h-80 overflow-y-auto alerto-scrollbar">
                    @forelse($liveActivity as $item)
                        <a href="{{ $item['url'] ?? '#' }}" class="block p-3 rounded-xl bg-white/5 hover:bg-[#E87524]/10 border border-white/10 transition-colors">
                            <div class="flex justify-between gap-2 font-bold">
                                <span class="truncate">{{ $item['title'] ?? '' }}</span>
                                <span class="text-[10px] text-[#9D9993] shrink-0">{{ $item['time_ago'] ?? '' }}</span>
                            </div>
                            <p class="text-[11px] text-[#9D9993] mt-0.5 truncate">{{ $item['subtitle'] ?? '' }}</p>
                        </a>
                    @empty
                        <p class="text-[#9D9993] py-6 text-center">No recent operational events.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</x-layouts.super-admin>
