<x-layouts.super-admin :title="'System Monitoring — LDRRMO Super Admin'">
    @php
        $summary = $monitor['summary'] ?? [];
        $users = $monitor['users'] ?? collect();
        $stats = $telemetry['stats'] ?? [];
    @endphp

    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-white">System-Wide Live Monitoring</h1>
                <p class="text-xs text-slate-400 mt-1">
                    Real-time oversight across all 5 account tiers (Super Admin, DIAL DAPH Admin, Office, Personnel, Citizen) and municipal response load.
                </p>
            </div>
            <a href="{{ route('super-admin.monitoring', ['account_type' => $accountType, 'status' => $statusFilter]) }}"
               class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold self-start">
                Refresh Telemetry
            </a>
        </div>

        {{-- Presence Breakdown --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3 text-xs">
            <div class="p-4 rounded-2xl bg-[#0B1726] border border-purple-500/40">
                <span class="text-[10px] uppercase font-bold text-purple-300 block">Super Admins</span>
                <p class="text-2xl font-black font-mono text-white mt-1">{{ (int) ($summary['super_admins_online'] ?? 0) }}</p>
            </div>
            <div class="p-4 rounded-2xl bg-[#0B1726] border border-indigo-500/40">
                <span class="text-[10px] uppercase font-bold text-indigo-300 block">Admins Online</span>
                <p class="text-2xl font-black font-mono text-white mt-1">{{ (int) ($summary['admins_online'] ?? 0) }}</p>
            </div>
            <div class="p-4 rounded-2xl bg-[#0B1726] border border-blue-500/40">
                <span class="text-[10px] uppercase font-bold text-blue-300 block">Offices Online</span>
                <p class="text-2xl font-black font-mono text-white mt-1">{{ (int) ($summary['offices_online'] ?? 0) }}</p>
            </div>
            <div class="p-4 rounded-2xl bg-[#0B1726] border border-emerald-500/40">
                <span class="text-[10px] uppercase font-bold text-emerald-300 block">Personnel Online</span>
                <p class="text-2xl font-black font-mono text-white mt-1">{{ (int) ($summary['personnel_online'] ?? 0) }}</p>
            </div>
            <div class="p-4 rounded-2xl bg-[#0B1726] border border-cyan-500/40">
                <span class="text-[10px] uppercase font-bold text-cyan-300 block">Citizens Online</span>
                <p class="text-2xl font-black font-mono text-white mt-1">{{ (int) ($summary['citizens_online'] ?? 0) }}</p>
            </div>
            <div class="p-4 rounded-2xl bg-[#0B1726] border border-amber-500/40">
                <span class="text-[10px] uppercase font-bold text-amber-300 block">Idle</span>
                <p class="text-2xl font-black font-mono text-white mt-1">{{ (int) ($summary['idle_accounts'] ?? 0) }}</p>
            </div>
            <div class="p-4 rounded-2xl bg-[#0B1726] border border-rose-500/40">
                <span class="text-[10px] uppercase font-bold text-rose-300 block">Conn. Lost</span>
                <p class="text-2xl font-black font-mono text-white mt-1">{{ (int) ($summary['connection_lost_accounts'] ?? 0) }}</p>
            </div>
            <div class="p-4 rounded-2xl bg-[#0B1726] border border-white/10">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Offline</span>
                <p class="text-2xl font-black font-mono text-white mt-1">{{ (int) ($summary['offline_accounts'] ?? 0) }}</p>
            </div>
        </div>

        {{-- Operational Load Summary --}}
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-xs">
            <div class="p-4 rounded-2xl bg-[#0B1726] border border-white/10">
                <span class="text-[10px] uppercase text-slate-400 block">Total Incidents</span>
                <strong class="text-xl font-mono text-white">{{ number_format((int) ($stats['total'] ?? 0)) }}</strong>
            </div>
            <div class="p-4 rounded-2xl bg-[#0B1726] border border-white/10">
                <span class="text-[10px] uppercase text-slate-400 block">Incoming Queue</span>
                <strong class="text-xl font-mono text-amber-300">{{ number_format((int) ($stats['incoming_reports'] ?? 0)) }}</strong>
            </div>
            <div class="p-4 rounded-2xl bg-[#0B1726] border border-white/10">
                <span class="text-[10px] uppercase text-slate-400 block">Active SOS</span>
                <strong class="text-xl font-mono text-rose-400">{{ number_format((int) ($stats['active_sos'] ?? 0)) }}</strong>
            </div>
            <div class="p-4 rounded-2xl bg-[#0B1726] border border-white/10">
                <span class="text-[10px] uppercase text-slate-400 block">Deployment Warnings</span>
                <strong class="text-xl font-mono text-amber-400">{{ number_format((int) ($stats['deployment_warnings'] ?? 0)) }}</strong>
            </div>
            <div class="p-4 rounded-2xl bg-[#0B1726] border border-white/10">
                <span class="text-[10px] uppercase text-slate-400 block">Resolved Cases</span>
                <strong class="text-xl font-mono text-emerald-400">{{ number_format((int) ($stats['resolved'] ?? 0)) }}</strong>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('super-admin.monitoring') }}" class="p-4 rounded-2xl bg-[#0B1726] border border-white/10 flex flex-wrap items-center gap-3 text-xs">
            <select name="account_type" class="px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white">
                <option value="all" {{ $accountType === 'all' ? 'selected' : '' }}>All Account Types</option>
                <option value="super_admin" {{ $accountType === 'super_admin' ? 'selected' : '' }}>LDRRMO Super Admin</option>
                <option value="admin" {{ $accountType === 'admin' ? 'selected' : '' }}>DIAL DAPH Admin</option>
                <option value="office" {{ $accountType === 'office' ? 'selected' : '' }}>Office Accounts</option>
                <option value="personnel" {{ $accountType === 'personnel' ? 'selected' : '' }}>Personnel Responders</option>
                <option value="citizen" {{ $accountType === 'citizen' ? 'selected' : '' }}>Citizen Accounts</option>
            </select>

            <select name="status" class="px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white">
                <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Presence States</option>
                <option value="online" {{ $statusFilter === 'online' ? 'selected' : '' }}>Online</option>
                <option value="idle" {{ $statusFilter === 'idle' ? 'selected' : '' }}>Idle</option>
                <option value="connection_lost" {{ $statusFilter === 'connection_lost' ? 'selected' : '' }}>Connection Lost</option>
                <option value="offline" {{ $statusFilter === 'offline' ? 'selected' : '' }}>Offline</option>
            </select>

            <button type="submit" class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold cursor-pointer">Apply Filter</button>
            <a href="{{ route('super-admin.monitoring') }}" class="px-3 py-2 rounded-xl border border-white/15 text-slate-300 font-bold">Reset</a>
        </form>

        {{-- Accounts Table --}}
        <div class="rounded-2xl bg-[#0B1726] border border-white/10 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-white/10 text-[10px] uppercase text-slate-400 bg-black/20">
                        <tr>
                            <th class="p-4">User</th>
                            <th class="p-4">Account Tier</th>
                            <th class="p-4">Affiliation</th>
                            <th class="p-4">Presence</th>
                            <th class="p-4">Last Heartbeat</th>
                            <th class="p-4">Device</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse($users as $u)
                            @php $st = $u['presence_status'] ?? 'offline'; @endphp
                            <tr class="hover:bg-white/5">
                                <td class="p-4">
                                    <div class="font-bold text-white">{{ $u['name'] }}</div>
                                    <div class="text-[10px] font-mono text-slate-400">{{ $u['email'] ?: $u['phone_number'] }}</div>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase bg-purple-500/20 text-purple-200">
                                        {{ strtoupper(str_replace('_', ' ', (string) $u['account_type'])) }}
                                    </span>
                                </td>
                                <td class="p-4 text-slate-300">{{ $u['affiliation'] }}</td>
                                <td class="p-4">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase {{ match($st) {
                                        'online' => 'bg-emerald-500/20 text-emerald-300',
                                        'idle' => 'bg-amber-500/20 text-amber-300',
                                        'connection_lost' => 'bg-rose-500/20 text-rose-300',
                                        default => 'bg-slate-500/20 text-slate-400',
                                    } }}">
                                        {{ str_replace('_', ' ', $st) }}
                                    </span>
                                </td>
                                <td class="p-4 text-slate-300">{{ $u['last_heartbeat_human'] }}</td>
                                <td class="p-4 font-mono text-[11px] text-slate-400">{{ $u['current_device'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400">No active accounts matched the selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.super-admin>

