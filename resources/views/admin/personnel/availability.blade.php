<x-layouts.admin :title="'Availability Monitor — ALERTO EOC'">
    <div class="space-y-6">

        {{-- Top Bar & Title --}}
        <x-portal.page-header
            badge="Operational Readiness · Real-Time Telemetry"
            title="Personnel Availability Monitor"
            subtitle="Live tracking of operational duty availability, digital heartbeat presence, and active field missions."
        >
            <x-slot:actions>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#27845B]/15 border border-[#27845B]/30 text-[#27845B] text-xs font-bold">
                    <span class="w-2 h-2 rounded-full bg-[#27845B] animate-pulse"></span> Live Telemetry Active
                </span>
            </x-slot:actions>
        </x-portal.page-header>

        {{-- Sub-navigation Tabs --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-[#E6E3DE] dark:border-white/10">
            <a href="{{ route('admin.personnel.index') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.index') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                <i data-lucide="users" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Directory
            </a>
            <a href="{{ route('admin.personnel.teams') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.teams') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                <i data-lucide="shield" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Response Teams
            </a>
            <a href="{{ route('admin.personnel.jurisdictions') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.jurisdictions') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                <i data-lucide="map-pin" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Jurisdictions
            </a>
            <a href="{{ route('admin.personnel.availability') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.availability') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                <i data-lucide="activity" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Availability Monitor
            </a>
            <a href="{{ route('admin.personnel.assignments') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.assignments') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                <i data-lucide="radio" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Active Assignments
            </a>
            <a href="{{ route('admin.personnel.bases') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.bases') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                <i data-lucide="home" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Response Bases
            </a>
        </div>

        {{-- Connection Lost Alert Banner --}}
        @if(($stats['connection_lost'] ?? 0) > 0)
            <div class="p-4 rounded-2xl bg-[#C83F43]/15 border border-[#C83F43]/30 text-[#C83F43] text-xs font-medium flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i data-lucide="wifi-off" class="w-5 h-5 text-[#C83F43] shrink-0"></i>
                    <div>
                        <span class="font-bold">{{ $stats['connection_lost'] }} Operative(s) Connection Lost:</span>
                        <span>Heartbeat gap exceeded 2 minutes. Units may be in cellular dead zones or experienced power loss.</span>
                    </div>
                </div>
                <a href="#personnel-roster" class="underline font-bold text-xs">Inspect Units</a>
            </div>
        @endif

        {{-- KPI Real-Time Metrics --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
            <div class="alerto-kpi-card alerto-card p-4">
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#77736E] block">Total Staff</span>
                <p class="text-2xl font-black font-mono mt-1">{{ $stats['total'] }}</p>
                <span class="text-[10px] text-[#77736E]">All Registered</span>
            </div>

            <div class="alerto-kpi-card alerto-card p-4 border-[#27845B]/30">
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#27845B] block">Available</span>
                <p class="text-2xl font-black font-mono text-[#27845B] mt-1">{{ $stats['available'] }}</p>
                <span class="text-[10px] text-[#77736E]">Dispatch Eligible</span>
            </div>

            <div class="alerto-kpi-card alerto-card p-4 border-[#397BA8]/30">
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#397BA8] block">Dispatched</span>
                <p class="text-2xl font-black font-mono text-[#397BA8] mt-1">{{ $stats['dispatched'] }}</p>
                <span class="text-[10px] text-[#77736E]">En Route</span>
            </div>

            <div class="alerto-kpi-card alerto-card p-4 border-[#E87524]/30">
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#B94F12] dark:text-[#F29A56] block">On Scene</span>
                <p class="text-2xl font-black font-mono text-[#E87524] mt-1">{{ $stats['on_scene'] }}</p>
                <span class="text-[10px] text-[#77736E]">At Emergency</span>
            </div>

            <div class="alerto-kpi-card alerto-card p-4 border-[#D99024]/30">
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#D99024] block">Busy</span>
                <p class="text-2xl font-black font-mono text-[#D99024] mt-1">{{ $stats['busy'] }}</p>
                <span class="text-[10px] text-[#77736E]">Occupied</span>
            </div>

            <div class="alerto-kpi-card alerto-card p-4">
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#77736E] block">Off Duty</span>
                <p class="text-2xl font-black font-mono mt-1">{{ $stats['off_duty'] }}</p>
                <span class="text-[10px] text-[#77736E]">Off Shift</span>
            </div>

            <div class="alerto-kpi-card alerto-card p-4 border-[#C83F43]/30">
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#C83F43] block">Conn Lost</span>
                <p class="text-2xl font-black font-mono text-[#C83F43] mt-1">{{ $stats['connection_lost'] }}</p>
                <span class="text-[10px] text-[#77736E]">&gt;2m Gap</span>
            </div>
        </div>

        {{-- Operatives Live Status Table --}}
        <div id="personnel-roster" class="alerto-card overflow-hidden">
            <div class="p-4 border-b border-[#E6E3DE] dark:border-white/10 bg-[#F5F3EF] dark:bg-white/5 flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider">Operatives Status Roster</h3>
                <span class="text-xs text-[#77736E] font-mono">Updated: {{ now()->format('H:i:s') }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F5F3EF]/70 dark:bg-white/5 border-b border-[#E6E3DE] dark:border-white/10 text-[#77736E] uppercase text-[10px] tracking-wider font-bold">
                        <tr>
                            <th class="py-3 px-4">Operative</th>
                            <th class="py-3 px-4">Office &amp; Teams</th>
                            <th class="py-3 px-4">Account Presence</th>
                            <th class="py-3 px-4">Operational Status</th>
                            <th class="py-3 px-4">Last Heartbeat</th>
                            <th class="py-3 px-4">Status Reason</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E6E3DE] dark:divide-white/10">
                        @foreach($personnel as $p)
                            @php
                                $presence = $p->user?->userPresence?->status ?? 'offline';
                                $opStatus = $p->availability?->operational_status ?? 'off_duty';
                                $lastBeat = $p->user?->userPresence?->last_heartbeat_at;
                            @endphp
                            <tr class="hover:bg-[#FFF0E4]/40 dark:hover:bg-white/5 transition-colors">
                                <td class="py-3 px-4">
                                    <div class="font-bold">{{ $p->user?->name }}</div>
                                    <div class="text-[11px] font-mono text-[#77736E]">#{{ $p->badge_number }} · {{ $p->rank_designation }}</div>
                                </td>

                                <td class="py-3 px-4">
                                    <div class="font-medium">{{ $p->office?->name ?? 'N/A' }}</div>
                                    <div class="flex flex-wrap gap-1 mt-0.5">
                                        @foreach($p->teams as $team)
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56] border border-[#E87524]/25">
                                                {{ $team->call_sign }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>

                                <td class="py-3 px-4">
                                    @if($presence === 'online')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#27845B]/15 text-[#27845B]">
                                            <span class="w-2 h-2 rounded-full bg-[#27845B] animate-pulse"></span> Online
                                        </span>
                                    @elseif($presence === 'connection_lost')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#C83F43]/15 text-[#C83F43] animate-bounce">
                                            <span class="w-2 h-2 rounded-full bg-[#C83F43]"></span> Lost
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-black/5 dark:bg-white/10 text-[#77736E]">
                                            <span class="w-2 h-2 rounded-full bg-[#77736E]"></span> Offline
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3 px-4">
                                    @if($opStatus === 'available')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold bg-[#27845B]/15 text-[#27845B] border border-[#27845B]/25">
                                            Available
                                        </span>
                                    @elseif($opStatus === 'dispatched')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold bg-[#397BA8]/15 text-[#397BA8] border border-[#397BA8]/25">
                                            Dispatched
                                        </span>
                                    @elseif($opStatus === 'on_scene')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56] border border-[#E87524]/25">
                                            On Scene
                                        </span>
                                    @elseif($opStatus === 'busy')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold bg-[#D99024]/15 text-[#D99024] border border-[#D99024]/25">
                                            Busy
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold bg-black/5 dark:bg-white/10 text-[#77736E]">
                                            Off Duty
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3 px-4 font-mono text-[11px] text-[#77736E]">
                                    {{ $lastBeat ? $lastBeat->diffForHumans() : 'Never' }}
                                </td>

                                <td class="py-3 px-4 text-[#77736E] text-[11px]">
                                    {{ $p->availability?->status_reason ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-layouts.admin>
