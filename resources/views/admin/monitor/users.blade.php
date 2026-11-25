<x-layouts.admin :title="'Live User Monitor — ALERTO EOC'">
    <div class="space-y-6" x-data="liveUserMonitor({{ Js::from($monitor) }})" x-init="initMonitor()">
        {{-- Header --}}
        <x-portal.page-header
            badge="Real-Time Session Presence · Account Heartbeat Telemetry"
            title="Live User Monitor"
        >
            <x-slot:subtitle>
                Monitors account session status (<code class="font-mono text-[#B94F12] dark:text-[#F29A56]">online</code>, <code class="font-mono text-[#D99024]">idle</code>, <code class="font-mono text-[#C83F43]">connection_lost</code>, <code class="font-mono">offline</code>) separately from physical responder GPS. Citizen physical locations are never tracked.
            </x-slot:subtitle>
            <x-slot:actions>
                <button type="button" x-on:click="syncMonitor()"
                        class="alerto-btn-press inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white text-xs font-bold shadow-xs">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5" :class="syncing ? 'animate-spin' : ''"></i>
                    <span>Refresh Presence</span>
                </button>
            </x-slot:actions>
        </x-portal.page-header>

        {{-- Privacy Compliance Notice --}}
        <div class="alerto-card p-3.5 px-4 flex items-center gap-3 text-xs border-[#E87524]/30">
            <i data-lucide="shield-check" class="w-4 h-4 text-[#E87524] shrink-0"></i>
            <span><strong>Presence Separation Enforced:</strong> Citizen sessions report authenticated application heartbeat only. Physical GPS coordinates are strictly captured only when explicitly submitting an incident report or dispatched responder shift telemetry.</span>
        </div>

        {{-- 6 Real Presence Metrics --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
            <div class="alerto-kpi-card alerto-card p-4 border-[#27845B]/40">
                <span class="text-[10px] font-bold uppercase text-[#27845B] block">Online Personnel</span>
                <p class="text-2xl font-black font-mono mt-1" x-text="summary.personnel_online">{{ $monitor['summary']['personnel_online'] }}</p>
            </div>
            <div class="alerto-kpi-card alerto-card p-4 border-[#E87524]/40">
                <span class="text-[10px] font-bold uppercase text-[#B94F12] dark:text-[#F29A56] block">Online Offices</span>
                <p class="text-2xl font-black font-mono mt-1" x-text="summary.offices_online">{{ $monitor['summary']['offices_online'] }}</p>
            </div>
            <div class="alerto-kpi-card alerto-card p-4 border-[#397BA8]/40">
                <span class="text-[10px] font-bold uppercase text-[#397BA8] block">Online Citizens</span>
                <p class="text-2xl font-black font-mono mt-1" x-text="summary.citizens_online">{{ $monitor['summary']['citizens_online'] }}</p>
            </div>
            <div class="alerto-kpi-card alerto-card p-4 border-[#D99024]/40">
                <span class="text-[10px] font-bold uppercase text-[#D99024] block">Idle Accounts</span>
                <p class="text-2xl font-black font-mono mt-1" x-text="summary.idle_accounts">{{ $monitor['summary']['idle_accounts'] }}</p>
            </div>
            <div class="alerto-kpi-card alerto-card p-4 border-[#C83F43]/40">
                <span class="text-[10px] font-bold uppercase text-[#C83F43] block">Connection Lost</span>
                <p class="text-2xl font-black font-mono mt-1" x-text="summary.connection_lost_accounts">{{ $monitor['summary']['connection_lost_accounts'] }}</p>
            </div>
            <div class="alerto-kpi-card alerto-card p-4">
                <span class="text-[10px] font-bold uppercase text-[#77736E] block">Offline Accounts</span>
                <p class="text-2xl font-black font-mono mt-1" x-text="summary.offline_accounts">{{ $monitor['summary']['offline_accounts'] }}</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="alerto-card p-4">
            <form method="GET" action="{{ route('admin.monitor.users') }}" class="flex flex-wrap items-center gap-3 text-xs">
                <select name="account_type" class="px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] text-xs focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                    <option value="all" {{ $accountType === 'all' ? 'selected' : '' }}>All Account Types</option>
                    <option value="personnel" {{ $accountType === 'personnel' ? 'selected' : '' }}>Personnel Responders</option>
                    <option value="office" {{ $accountType === 'office' ? 'selected' : '' }}>Office Accounts</option>
                    <option value="citizen" {{ $accountType === 'citizen' ? 'selected' : '' }}>Citizen Accounts</option>
                    <option value="admin" {{ $accountType === 'admin' ? 'selected' : '' }}>Admin Directors</option>
                </select>
                <select name="status" class="px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] text-xs focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Presence States</option>
                    <option value="online" {{ $statusFilter === 'online' ? 'selected' : '' }}>Online</option>
                    <option value="idle" {{ $statusFilter === 'idle' ? 'selected' : '' }}>Idle</option>
                    <option value="connection_lost" {{ $statusFilter === 'connection_lost' ? 'selected' : '' }}>Connection Lost</option>
                    <option value="offline" {{ $statusFilter === 'offline' ? 'selected' : '' }}>Offline</option>
                </select>
                <button type="submit" class="alerto-btn-press px-4 py-2 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white font-bold">Filter Accounts</button>
                <a href="{{ route('admin.monitor.users') }}" class="px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 font-bold hover:bg-[#F5F3EF] dark:hover:bg-white/10">Reset</a>
            </form>
        </div>

        {{-- Account Presence Panels --}}
        <div class="alerto-card overflow-hidden">
            <div class="overflow-x-auto alerto-scrollbar">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-[#E6E3DE] dark:border-white/10 text-[10px] font-bold uppercase tracking-wider text-[#77736E] bg-[#F5F3EF] dark:bg-white/5">
                            <th class="py-3.5 px-5">User / Operator</th>
                            <th class="py-3.5 px-5">Account Type</th>
                            <th class="py-3.5 px-5">Unit / Affiliation</th>
                            <th class="py-3.5 px-5">Presence Status</th>
                            <th class="py-3.5 px-5">Last Heartbeat</th>
                            <th class="py-3.5 px-5">Device Session</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E6E3DE] dark:divide-white/10">
                        <template x-for="u in users" :key="u.id">
                            <tr class="hover:bg-[#FFF0E4]/40 dark:hover:bg-white/5 transition-colors">
                                <td class="py-3.5 px-5">
                                    <div class="font-bold" x-text="u.name"></div>
                                    <div class="text-[10px] font-mono text-[#77736E]" x-text="u.email || u.phone_number"></div>
                                </td>
                                <td class="py-3.5 px-5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56]" x-text="u.account_type"></span>
                                </td>
                                <td class="py-3.5 px-5 text-xs" x-text="u.affiliation"></td>
                                <td class="py-3.5 px-5">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase"
                                          :class="{
                                              'bg-[#27845B]/15 text-[#27845B]': u.presence_status === 'online',
                                              'bg-[#D99024]/15 text-[#D99024]': u.presence_status === 'idle',
                                              'bg-[#C83F43]/15 text-[#C83F43]': u.presence_status === 'connection_lost',
                                              'bg-black/5 dark:bg-white/10 text-[#77736E]': u.presence_status === 'offline'
                                          }">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                              :class="{
                                                  'bg-[#27845B] animate-pulse': u.presence_status === 'online',
                                                  'bg-[#D99024]': u.presence_status === 'idle',
                                                  'bg-[#C83F43]': u.presence_status === 'connection_lost',
                                                  'bg-[#77736E]': u.presence_status === 'offline'
                                              }"></span>
                                        <span x-text="u.presence_status.replace('_', ' ')"></span>
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 font-mono text-[11px] text-[#77736E]" x-text="u.last_heartbeat_human"></td>
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
