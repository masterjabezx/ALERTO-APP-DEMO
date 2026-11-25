<x-layouts.admin :title="'Live User Monitor — ALERTO EOC'">
    <div class="space-y-6" x-data="liveUserMonitor({{ Js::from($monitor) }})" x-init="initMonitor()">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#19865B]/15 text-[#19865B] text-[11px] font-bold uppercase tracking-wider mb-1">
                    <span class="w-2 h-2 rounded-full bg-[#19865B] animate-pulse"></span>
                    Real-Time Session Presence · Account Heartbeat Telemetry
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Live User Monitor</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1">
                    Monitors account session status (<code class="font-mono text-[#34B9D4]">online</code>, <code class="font-mono text-[#F0A82E]">idle</code>, <code class="font-mono text-[#DB4545]">connection_lost</code>, <code class="font-mono">offline</code>) separately from physical responder GPS. Citizen physical locations are never tracked.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" x-on:click="syncMonitor()"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#3187D8] hover:bg-[#2770b6] text-white text-xs font-bold shadow-xs">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5" :class="syncing ? 'animate-spin' : ''"></i>
                    <span>Refresh Presence</span>
                </button>
            </div>
        </div>

        {{-- Privacy Compliance Notice --}}
        <div class="eoc-card p-3.5 px-4 flex items-center gap-3 text-xs border-[#34B9D4]/30">
            <i data-lucide="shield-check" class="w-4 h-4 text-[#34B9D4] shrink-0"></i>
            <span><strong>Presence Separation Enforced:</strong> Citizen sessions report authenticated application heartbeat only. Physical GPS coordinates are strictly captured only when explicitly submitting an incident report or dispatched responder shift telemetry.</span>
        </div>

        {{-- 6 Real Presence Metrics --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
            <div class="eoc-kpi-card eoc-card p-4 border-[#19865B]/40">
                <span class="text-[10px] font-bold uppercase text-[#19865B] block">Online Personnel</span>
                <p class="text-2xl font-black font-mono mt-1" x-text="summary.personnel_online">{{ $monitor['summary']['personnel_online'] }}</p>
            </div>
            <div class="eoc-kpi-card eoc-card p-4 border-[#3187D8]/40">
                <span class="text-[10px] font-bold uppercase text-[#3187D8] block">Online Offices</span>
                <p class="text-2xl font-black font-mono mt-1" x-text="summary.offices_online">{{ $monitor['summary']['offices_online'] }}</p>
            </div>
            <div class="eoc-kpi-card eoc-card p-4 border-[#34B9D4]/40">
                <span class="text-[10px] font-bold uppercase text-[#34B9D4] block">Online Citizens</span>
                <p class="text-2xl font-black font-mono mt-1" x-text="summary.citizens_online">{{ $monitor['summary']['citizens_online'] }}</p>
            </div>
            <div class="eoc-kpi-card eoc-card p-4 border-[#F0A82E]/40">
                <span class="text-[10px] font-bold uppercase text-[#F0A82E] block">Idle Accounts</span>
                <p class="text-2xl font-black font-mono mt-1" x-text="summary.idle_accounts">{{ $monitor['summary']['idle_accounts'] }}</p>
            </div>
            <div class="eoc-kpi-card eoc-card p-4 border-[#DB4545]/40">
                <span class="text-[10px] font-bold uppercase text-[#DB4545] block">Connection Lost</span>
                <p class="text-2xl font-black font-mono mt-1" x-text="summary.connection_lost_accounts">{{ $monitor['summary']['connection_lost_accounts'] }}</p>
            </div>
            <div class="eoc-kpi-card eoc-card p-4">
                <span class="text-[10px] font-bold uppercase text-slate-400 block">Offline Accounts</span>
                <p class="text-2xl font-black font-mono mt-1" x-text="summary.offline_accounts">{{ $monitor['summary']['offline_accounts'] }}</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="eoc-card p-4">
            <form method="GET" action="{{ route('admin.monitor.users') }}" class="flex flex-wrap items-center gap-3 text-xs">
                <select name="account_type" class="px-3 py-2 rounded-xl border text-xs">
                    <option value="all" {{ $accountType === 'all' ? 'selected' : '' }}>All Account Types</option>
                    <option value="personnel" {{ $accountType === 'personnel' ? 'selected' : '' }}>Personnel Responders</option>
                    <option value="office" {{ $accountType === 'office' ? 'selected' : '' }}>Office Accounts</option>
                    <option value="citizen" {{ $accountType === 'citizen' ? 'selected' : '' }}>Citizen Accounts</option>
                    <option value="admin" {{ $accountType === 'admin' ? 'selected' : '' }}>Admin Directors</option>
                </select>
                <select name="status" class="px-3 py-2 rounded-xl border text-xs">
                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Presence States</option>
                    <option value="online" {{ $statusFilter === 'online' ? 'selected' : '' }}>Online</option>
                    <option value="idle" {{ $statusFilter === 'idle' ? 'selected' : '' }}>Idle</option>
                    <option value="connection_lost" {{ $statusFilter === 'connection_lost' ? 'selected' : '' }}>Connection Lost</option>
                    <option value="offline" {{ $statusFilter === 'offline' ? 'selected' : '' }}>Offline</option>
                </select>
                <button type="submit" class="px-4 py-2 rounded-xl bg-[#3187D8] text-white font-bold">Filter Accounts</button>
                <a href="{{ route('admin.monitor.users') }}" class="px-3 py-2 rounded-xl border border-slate-400/30 font-bold">Reset</a>
            </form>
        </div>

        {{-- Account Presence Panels --}}
        <div class="eoc-card overflow-hidden">
            <div class="overflow-x-auto alerto-scrollbar">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-400/20 text-[10px] font-bold uppercase tracking-wider text-slate-400 bg-black/15">
                            <th class="py-3.5 px-5">User / Operator</th>
                            <th class="py-3.5 px-5">Account Type</th>
                            <th class="py-3.5 px-5">Unit / Affiliation</th>
                            <th class="py-3.5 px-5">Presence Status</th>
                            <th class="py-3.5 px-5">Last Heartbeat</th>
                            <th class="py-3.5 px-5">Device Session</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-400/15">
                        <template x-for="u in users" :key="u.id">
                            <tr class="hover:bg-[#3187D8]/5 transition-colors">
                                <td class="py-3.5 px-5">
                                    <div class="font-bold" x-text="u.name"></div>
                                    <div class="text-[10px] font-mono text-slate-400" x-text="u.email || u.phone_number"></div>
                                </td>
                                <td class="py-3.5 px-5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase bg-[#173957] text-[#34B9D4]" x-text="u.account_type"></span>
                                </td>
                                <td class="py-3.5 px-5 text-xs" x-text="u.affiliation"></td>
                                <td class="py-3.5 px-5">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase"
                                          :class="{
                                              'bg-[#19865B]/20 text-[#6AE0B0]': u.presence_status === 'online',
                                              'bg-[#F0A82E]/20 text-[#F7C768]': u.presence_status === 'idle',
                                              'bg-[#DB4545]/20 text-[#FDA4AF]': u.presence_status === 'connection_lost',
                                              'bg-slate-500/20 text-slate-400': u.presence_status === 'offline'
                                          }">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                              :class="{
                                                  'bg-[#19865B] animate-pulse': u.presence_status === 'online',
                                                  'bg-[#F0A82E]': u.presence_status === 'idle',
                                                  'bg-[#DB4545]': u.presence_status === 'connection_lost',
                                                  'bg-slate-400': u.presence_status === 'offline'
                                              }"></span>
                                        <span x-text="u.presence_status.replace('_', ' ')"></span>
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 font-mono text-[11px] text-slate-400" x-text="u.last_heartbeat_human"></td>
                                <td class="py-3.5 px-5 text-[11px] font-mono" x-text="u.current_device"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function liveUserMonitor(initial) {
            return {
                summary: initial.summary || {},
                users: initial.users || [],
                syncing: false,

                initMonitor() {
                    setInterval(() => this.syncMonitor(true), 20000);
                },

                async syncMonitor(silent = false) {
                    if (!silent) this.syncing = true;
                    try {
                        const params = new URLSearchParams(window.location.search);
                        const res = await fetch('{{ route('admin.monitor.users.data') }}?' + params.toString(), {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (res.ok) {
                            const data = await res.json();
                            this.summary = data.summary || this.summary;
                            this.users = data.users || this.users;
                        }
                    } finally {
                        if (!silent) this.syncing = false;
                    }
                }
            };
        }
    </script>
</x-layouts.admin>

