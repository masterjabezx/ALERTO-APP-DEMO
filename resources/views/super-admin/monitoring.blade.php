<x-layouts.super-admin :title="'System Monitoring — LDRRMO Super Admin'">
    @php
        $summary = $monitor['summary'] ?? [];
        $users = $monitor['users'] ?? collect();
        $stats = $telemetry['stats'] ?? [];
    @endphp

    <div class="space-y-6">
        <x-portal.page-header
            eyebrow="Executive Presence &amp; Load"
            title="System-Wide Live Monitoring"
            subtitle="Real-time oversight across all 5 account tiers (Super Admin, DIAL DAPH Admin, Office, Personnel, Citizen) and municipal response load."
            :breadcrumbs="[['label' => 'Executive Dashboard', 'href' => route('super-admin.dashboard')], ['label' => 'System Monitoring']]"
        >
            <x-slot:actions>
                <a href="{{ route('super-admin.monitoring', ['account_type' => $accountType, 'status' => $statusFilter]) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white text-xs font-bold shadow-xs transition-colors btn-fixed">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    <span>Refresh Telemetry</span>
                </a>
            </x-slot:actions>
        </x-portal.page-header>

        {{-- Presence Breakdown --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3 text-xs">
            <div class="alerto-kpi-card eoc-card p-4 border-t-2 border-t-[#E87524]">
                <span class="text-[10px] uppercase font-bold text-[#E87524] block">Super Admins</span>
                <p class="text-2xl font-black font-mono mt-1">{{ (int) ($summary['super_admins_online'] ?? 0) }}</p>
            </div>
            <div class="alerto-kpi-card eoc-card p-4 border-t-2 border-t-[#F29A56]">
                <span class="text-[10px] uppercase font-bold text-[#F29A56] block">Admins Online</span>
                <p class="text-2xl font-black font-mono mt-1">{{ (int) ($summary['admins_online'] ?? 0) }}</p>
            </div>
            <div class="alerto-kpi-card eoc-card p-4 border-t-2 border-t-[#397BA8]">
                <span class="text-[10px] uppercase font-bold text-[#397BA8] block">Offices Online</span>
                <p class="text-2xl font-black font-mono mt-1">{{ (int) ($summary['offices_online'] ?? 0) }}</p>
            </div>
            <div class="alerto-kpi-card eoc-card p-4 border-t-2 border-t-[#27845B]">
                <span class="text-[10px] uppercase font-bold text-[#27845B] block">Personnel Online</span>
                <p class="text-2xl font-black font-mono mt-1">{{ (int) ($summary['personnel_online'] ?? 0) }}</p>
            </div>
            <div class="alerto-kpi-card eoc-card p-4 border-t-2 border-t-[#77736E]">
                <span class="text-[10px] uppercase font-bold text-[#9D9993] block">Citizens Online</span>
                <p class="text-2xl font-black font-mono mt-1">{{ (int) ($summary['citizens_online'] ?? 0) }}</p>
            </div>
            <div class="alerto-kpi-card eoc-card p-4 border-t-2 border-t-[#D99024]">
                <span class="text-[10px] uppercase font-bold text-[#D99024] block">Idle</span>
                <p class="text-2xl font-black font-mono mt-1">{{ (int) ($summary['idle_accounts'] ?? 0) }}</p>
            </div>
            <div class="alerto-kpi-card eoc-card p-4 border-t-2 border-t-[#C83F43]">
                <span class="text-[10px] uppercase font-bold text-[#C83F43] block">Conn. Lost</span>
                <p class="text-2xl font-black font-mono mt-1">{{ (int) ($summary['connection_lost_accounts'] ?? 0) }}</p>
            </div>
            <div class="alerto-kpi-card eoc-card p-4 border-t-2 border-t-[#454A50]">
                <span class="text-[10px] uppercase font-bold text-[#9D9993] block">Offline</span>
                <p class="text-2xl font-black font-mono mt-1">{{ (int) ($summary['offline_accounts'] ?? 0) }}</p>
            </div>
        </div>

        {{-- Operational Load Summary --}}
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-xs">
            <div class="eoc-card p-4">
                <span class="text-[10px] uppercase text-[#9D9993] block font-bold">Total Incidents</span>
                <strong class="text-xl font-mono">{{ number_format((int) ($stats['total'] ?? 0)) }}</strong>
            </div>
            <div class="eoc-card p-4">
                <span class="text-[10px] uppercase text-[#9D9993] block font-bold">Incoming Queue</span>
                <strong class="text-xl font-mono text-[#E87524]">{{ number_format((int) ($stats['incoming_reports'] ?? 0)) }}</strong>
            </div>
            <div class="eoc-card p-4">
                <span class="text-[10px] uppercase text-[#9D9993] block font-bold">Active SOS</span>
                <strong class="text-xl font-mono text-[#C83F43]">{{ number_format((int) ($stats['active_sos'] ?? 0)) }}</strong>
            </div>
            <div class="eoc-card p-4">
                <span class="text-[10px] uppercase text-[#9D9993] block font-bold">Deployment Warnings</span>
                <strong class="text-xl font-mono text-[#D99024]">{{ number_format((int) ($stats['deployment_warnings'] ?? 0)) }}</strong>
            </div>
            <div class="eoc-card p-4">
                <span class="text-[10px] uppercase text-[#9D9993] block font-bold">Resolved Cases</span>
                <strong class="text-xl font-mono text-[#27845B]">{{ number_format((int) ($stats['resolved'] ?? 0)) }}</strong>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('super-admin.monitoring') }}" class="eoc-card p-4 flex flex-wrap items-center gap-3 text-xs">
            <select name="account_type" class="min-h-[40px] px-3 py-2 rounded-xl border text-xs">
                <option value="all" {{ $accountType === 'all' ? 'selected' : '' }}>All Account Types</option>
                <option value="super_admin" {{ $accountType === 'super_admin' ? 'selected' : '' }}>LDRRMO Super Admin</option>
                <option value="admin" {{ $accountType === 'admin' ? 'selected' : '' }}>DIAL DAPH Admin</option>
                <option value="office" {{ $accountType === 'office' ? 'selected' : '' }}>Office Accounts</option>
                <option value="personnel" {{ $accountType === 'personnel' ? 'selected' : '' }}>Personnel Responders</option>
                <option value="citizen" {{ $accountType === 'citizen' ? 'selected' : '' }}>Citizen Accounts</option>
            </select>

            <select name="status" class="min-h-[40px] px-3 py-2 rounded-xl border text-xs">
                <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Presence States</option>
                <option value="online" {{ $statusFilter === 'online' ? 'selected' : '' }}>Online</option>
                <option value="idle" {{ $statusFilter === 'idle' ? 'selected' : '' }}>Idle</option>
                <option value="connection_lost" {{ $statusFilter === 'connection_lost' ? 'selected' : '' }}>Connection Lost</option>
                <option value="offline" {{ $statusFilter === 'offline' ? 'selected' : '' }}>Offline</option>
            </select>

            <button type="submit" class="px-4 py-2 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white font-bold cursor-pointer btn-fixed">Apply Filter</button>
            <a href="{{ route('super-admin.monitoring') }}" class="px-3.5 py-2 rounded-xl border border-white/15 font-bold hover:border-[#E87524] transition-colors">Reset</a>
        </form>

        {{-- Accounts Table --}}
        <div class="eoc-card overflow-hidden">
            <div class="overflow-x-auto alerto-scrollbar">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-white/10 text-[10px] uppercase text-[#9D9993] bg-black/15">
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
                            <tr>
                                <td class="p-4">
                                    <div class="font-bold">{{ $u['name'] }}</div>
                                    <div class="text-[10px] font-mono text-[#9D9993]">{{ $u['email'] ?: $u['phone_number'] }}</div>
                                </td>
                                <td class="p-4">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase bg-[#E87524]/15 text-[#E87524]">
                                        {{ strtoupper(str_replace('_', ' ', (string) $u['account_type'])) }}
                                    </span>
                                </td>
                                <td class="p-4 text-[#D4D0C9]">{{ $u['affiliation'] }}</td>
                                <td class="p-4">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase {{ match($st) {
                                        'online' => 'bg-[#27845B]/20 text-[#27845B]',
                                        'idle' => 'bg-[#D99024]/20 text-[#D99024]',
                                        'connection_lost' => 'bg-[#C83F43]/20 text-[#C83F43]',
                                        default => 'bg-[#77736E]/20 text-[#9D9993]',
                                    } }}">
                                        {{ str_replace('_', ' ', $st) }}
                                    </span>
                                </td>
                                <td class="p-4 text-[#D4D0C9]">{{ $u['last_heartbeat_human'] }}</td>
                                <td class="p-4 font-mono text-[11px] text-[#9D9993]">{{ $u['current_device'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-10 text-center text-[#9D9993]">No active accounts matched the selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.super-admin>
