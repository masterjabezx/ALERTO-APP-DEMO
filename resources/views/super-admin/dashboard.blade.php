<x-layouts.super-admin :title="'Executive Dashboard — LDRRMO Super Admin'">
    @php
        $stats = $telemetry['stats'] ?? [];
        $presence = $telemetry['presence'] ?? [];
        $incomingReports = $telemetry['incoming_reports'] ?? collect();
        $activeSosAlerts = $telemetry['active_sos_alerts'] ?? collect();
        $liveActivity = $telemetry['live_activity'] ?? [];
    @endphp

    <div class="space-y-6">
        {{-- Executive Hero --}}
        <section class="rounded-3xl p-6 sm:p-8 bg-gradient-to-br from-[#130F26] via-[#0F1B2E] to-[#0B1726] border border-purple-500/30 shadow-2xl">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="space-y-2">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-500/20 border border-purple-400/40 text-purple-200 text-[11px] font-mono font-bold uppercase tracking-wider">
                        <span class="w-2 h-2 rounded-full bg-purple-400 animate-ping"></span>
                        LDRRMO SUPER ADMIN · HINOBA-AN MUNICIPAL GOVERNANCE
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                        Executive Command &amp; System Oversight
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-300 max-w-2xl">
                        Full governance authority across DIAL DAPH Command Center operations, automated emergency &amp; SOS routing rules, admin account permissions, and cryptographic database backups.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2.5 text-xs">
                    <a href="{{ route('super-admin.admins.index') }}" class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-lg">
                        Manage Admin Accounts
                    </a>
                    <a href="{{ route('super-admin.routing.index') }}" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold shadow-lg">
                        Routing &amp; Categories
                    </a>
                    <a href="{{ route('super-admin.backups.index') }}" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/15 text-white font-bold">
                        Backup &amp; Restore
                    </a>
                </div>
            </div>
        </section>

        {{-- Operational & Governance KPIs --}}
        <section class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 text-xs">
            <a href="{{ route('super-admin.incoming.index') }}" class="p-4 rounded-2xl bg-[#0B1726] border border-amber-500/35 hover:border-amber-400 transition">
                <span class="text-[10px] font-bold uppercase text-amber-300 block">Incoming Reports</span>
                <p class="text-2xl font-black font-mono text-white mt-1">{{ number_format((int) ($stats['incoming_reports'] ?? 0)) }}</p>
                <span class="text-[10px] text-slate-400">Awaiting DIAL DAPH call</span>
            </a>
            <a href="{{ route('super-admin.sos.index') }}" class="p-4 rounded-2xl bg-[#0B1726] border border-rose-500/40 hover:border-rose-400 transition">
                <span class="text-[10px] font-bold uppercase text-rose-300 block">Active SOS</span>
                <p class="text-2xl font-black font-mono text-white mt-1">{{ number_format((int) ($stats['active_sos'] ?? 0)) }}</p>
                <span class="text-[10px] text-slate-400">Citizen, Guest &amp; Responder</span>
            </a>
            <a href="{{ route('super-admin.incidents.index') }}" class="p-4 rounded-2xl bg-[#0B1726] border border-cyan-500/30 hover:border-cyan-400 transition">
                <span class="text-[10px] font-bold uppercase text-cyan-300 block">Active Responses</span>
                <p class="text-2xl font-black font-mono text-white mt-1">{{ number_format((int) (($stats['dispatched'] ?? 0) + ($stats['active_incidents'] ?? 0))) }}</p>
                <span class="text-[10px] text-slate-400">{{ number_format((int) ($stats['critical_count'] ?? 0)) }} critical</span>
            </a>
            <a href="{{ route('super-admin.admins.index') }}" class="p-4 rounded-2xl bg-[#0B1726] border border-purple-500/35 hover:border-purple-400 transition">
                <span class="text-[10px] font-bold uppercase text-purple-300 block">Command Admins</span>
                <p class="text-2xl font-black font-mono text-white mt-1">{{ number_format($adminsCount) }}</p>
                <span class="text-[10px] text-slate-400">{{ $superAdminsCount }} Super Admin(s)</span>
            </a>
            <a href="{{ route('super-admin.routing.index') }}" class="p-4 rounded-2xl bg-[#0B1726] border border-indigo-500/35 hover:border-indigo-400 transition">
                <span class="text-[10px] font-bold uppercase text-indigo-300 block">Active Routing Rules</span>
                <p class="text-2xl font-black font-mono text-white mt-1">{{ number_format($routingRulesCount + $sosRulesCount) }}</p>
                <span class="text-[10px] text-slate-400">{{ $routingRulesCount }} Incident · {{ $sosRulesCount }} SOS</span>
            </a>
            <a href="{{ route('super-admin.backups.index') }}" class="p-4 rounded-2xl bg-[#0B1726] border border-emerald-500/35 hover:border-emerald-400 transition">
                <span class="text-[10px] font-bold uppercase text-emerald-300 block">Latest Backup</span>
                <p class="text-sm font-black font-mono text-white mt-1 truncate">{{ $latestBackup?->created_at?->diffForHumans() ?? 'None yet' }}</p>
                <span class="text-[10px] text-slate-400 truncate block">{{ $latestBackup?->backup_name ?? 'Create initial snapshot' }}</span>
            </a>
        </section>

        {{-- Live Presence Strip --}}
        <section class="p-5 rounded-2xl bg-[#0B1726] border border-white/10">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <h2 class="text-xs font-black uppercase tracking-wider text-purple-300">Live Account Presence Snapshot</h2>
                <a href="{{ route('super-admin.monitoring') }}" class="text-xs font-bold text-cyan-300 hover:underline">Open System Monitoring &rarr;</a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 text-xs">
                <div class="p-3 rounded-xl bg-white/5"><span class="text-[10px] text-slate-400 block">Admins Online</span><strong class="text-lg font-mono text-purple-300">{{ (int) ($presence['admins_online'] ?? 0) }}</strong></div>
                <div class="p-3 rounded-xl bg-white/5"><span class="text-[10px] text-slate-400 block">Offices Online</span><strong class="text-lg font-mono text-blue-300">{{ (int) ($presence['offices_online'] ?? 0) }}</strong></div>
                <div class="p-3 rounded-xl bg-white/5"><span class="text-[10px] text-slate-400 block">Personnel Online</span><strong class="text-lg font-mono text-emerald-300">{{ (int) ($presence['personnel_online'] ?? 0) }}</strong></div>
                <div class="p-3 rounded-xl bg-white/5"><span class="text-[10px] text-slate-400 block">Citizens Online</span><strong class="text-lg font-mono text-cyan-300">{{ (int) ($presence['citizens_online'] ?? 0) }}</strong></div>
                <div class="p-3 rounded-xl bg-white/5"><span class="text-[10px] text-slate-400 block">Idle</span><strong class="text-lg font-mono text-amber-300">{{ (int) ($presence['idle_accounts'] ?? 0) }}</strong></div>
                <div class="p-3 rounded-xl bg-white/5"><span class="text-[10px] text-slate-400 block">Conn. Lost</span><strong class="text-lg font-mono text-rose-300">{{ (int) ($presence['connection_lost_accounts'] ?? 0) }}</strong></div>
                <div class="p-3 rounded-xl bg-white/5"><span class="text-[10px] text-slate-400 block">Offline</span><strong class="text-lg font-mono text-slate-300">{{ (int) ($presence['offline_accounts'] ?? 0) }}</strong></div>
            </div>
        </section>

        {{-- Live Queues & Activity Feed --}}
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-5 text-xs">
            <div class="p-5 rounded-2xl bg-[#0B1726] border border-rose-500/30 space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="font-black uppercase tracking-wider text-rose-400">Active SOS Alerts</h2>
                    <a href="{{ route('super-admin.sos.index') }}" class="text-cyan-300 font-bold hover:underline">View All</a>
                </div>
                @forelse($activeSosAlerts as $sos)
                    <a href="{{ route('super-admin.sos.show', $sos->id) }}" class="block p-3 rounded-xl bg-rose-950/30 border border-rose-500/30 hover:bg-rose-950/50">
                        <div class="flex justify-between font-mono font-bold text-white">
                            <span>{{ $sos->reference_number }}</span>
                            <span class="text-rose-300 uppercase">{{ $sos->sender_type }}</span>
                        </div>
                        <p class="text-slate-300 mt-1">{{ $sos->sender_name }} · {{ $sos->barangay?->name ?? 'Unverified Location' }}</p>
                    </a>
                @empty
                    <p class="text-slate-400 py-6 text-center">No active SOS alerts.</p>
                @endforelse
            </div>

            <div class="p-5 rounded-2xl bg-[#0B1726] border border-amber-500/30 space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="font-black uppercase tracking-wider text-amber-400">Incoming Reports</h2>
                    <a href="{{ route('super-admin.incoming.index') }}" class="text-cyan-300 font-bold hover:underline">View All</a>
                </div>
                @forelse($incomingReports as $rep)
                    <a href="{{ route('super-admin.incidents.show', $rep->reference_number) }}" class="block p-3 rounded-xl bg-amber-950/20 border border-amber-500/30 hover:bg-amber-950/40">
                        <div class="flex justify-between font-mono font-bold text-white">
                            <span>{{ $rep->reference_number }}</span>
                            <span class="text-amber-300 uppercase">{{ str_replace('_', ' ', $rep->workflow_stage ?? 'new_report') }}</span>
                        </div>
                        <p class="text-slate-300 mt-1">{{ $rep->caller_name }} ({{ $rep->caller_phone }}) · {{ $rep->barangay?->name ?? 'Pin Pending' }}</p>
                    </a>
                @empty
                    <p class="text-slate-400 py-6 text-center">No unclassified incoming reports.</p>
                @endforelse
            </div>

            <div class="p-5 rounded-2xl bg-[#0B1726] border border-white/10 space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="font-black uppercase tracking-wider text-slate-300">Operational Timeline</h2>
                    <a href="{{ route('super-admin.audit.index') }}" class="text-cyan-300 font-bold hover:underline">Audit Logs</a>
                </div>
                <div class="space-y-2 max-h-80 overflow-y-auto alerto-scrollbar">
                    @forelse($liveActivity as $item)
                        <a href="{{ $item['url'] ?? '#' }}" class="block p-3 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10">
                            <div class="flex justify-between gap-2 font-bold text-white">
                                <span class="truncate">{{ $item['title'] ?? '' }}</span>
                                <span class="text-[10px] text-slate-400 shrink-0">{{ $item['time_ago'] ?? '' }}</span>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-0.5 truncate">{{ $item['subtitle'] ?? '' }}</p>
                        </a>
                    @empty
                        <p class="text-slate-400 py-6 text-center">No recent operational events.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</x-layouts.super-admin>

