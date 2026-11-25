<x-layouts.admin>
    <div class="space-y-6">

        {{-- Top Bar & Title --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#173957]/10 text-[#173957] text-[11px] font-bold uppercase tracking-wider mb-1">
                    <i data-lucide="activity" class="w-3.5 h-3.5 text-[#3187D8]"></i>
                    Operational Readiness · Real-Time Telemetry
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                    Personnel Availability Monitor
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Live tracking of operational duty availability, digital heartbeat presence, and active field missions.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Live Telemetry Active
                </span>
            </div>
        </div>

        {{-- Sub-navigation Tabs --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-slate-200">
            <a href="{{ route('admin.personnel.index') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.index') ? 'bg-[#173957] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <i data-lucide="users" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Directory
            </a>
            <a href="{{ route('admin.personnel.teams') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.teams') ? 'bg-[#173957] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <i data-lucide="shield" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Response Teams
            </a>
            <a href="{{ route('admin.personnel.jurisdictions') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.jurisdictions') ? 'bg-[#173957] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <i data-lucide="map-pin" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Jurisdictions
            </a>
            <a href="{{ route('admin.personnel.availability') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.availability') ? 'bg-[#173957] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <i data-lucide="activity" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Availability Monitor
            </a>
            <a href="{{ route('admin.personnel.assignments') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.assignments') ? 'bg-[#173957] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <i data-lucide="radio" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Active Assignments
            </a>
            <a href="{{ route('admin.personnel.bases') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.bases') ? 'bg-[#173957] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <i data-lucide="home" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Response Bases
            </a>
        </div>

        {{-- Connection Lost Alert Banner --}}
        @if(($stats['connection_lost'] ?? 0) > 0)
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i data-lucide="wifi-off" class="w-5 h-5 text-rose-600 shrink-0"></i>
                    <div>
                        <span class="font-bold">{{ $stats['connection_lost'] }} Operative(s) Connection Lost:</span>
                        <span>Heartbeat gap exceeded 2 minutes. Units may be in cellular dead zones or experienced power loss.</span>
                    </div>
                </div>
                <a href="#personnel-roster" class="text-rose-700 underline font-bold text-xs">Inspect Units</a>
            </div>
        @endif

        {{-- KPI Real-Time Metrics --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
            <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Total Staff</span>
                <p class="text-2xl font-black text-slate-900 mt-1">{{ $stats['total'] }}</p>
                <span class="text-[10px] text-slate-400">All Registered</span>
            </div>

            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 block">Available</span>
                <p class="text-2xl font-black text-emerald-900 mt-1">{{ $stats['available'] }}</p>
                <span class="text-[10px] text-emerald-700/80">Dispatch Eligible</span>
            </div>

            <div class="p-4 rounded-2xl bg-blue-50 border border-blue-200 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-blue-700 block">Dispatched</span>
                <p class="text-2xl font-black text-blue-900 mt-1">{{ $stats['dispatched'] }}</p>
                <span class="text-[10px] text-blue-700/80">En Route</span>
            </div>

            <div class="p-4 rounded-2xl bg-purple-50 border border-purple-200 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700 block">On Scene</span>
                <p class="text-2xl font-black text-purple-900 mt-1">{{ $stats['on_scene'] }}</p>
                <span class="text-[10px] text-purple-700/80">At Emergency</span>
            </div>

            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 block">Busy</span>
                <p class="text-2xl font-black text-amber-900 mt-1">{{ $stats['busy'] }}</p>
                <span class="text-[10px] text-amber-700/80">Occupied</span>
            </div>

            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-600 block">Off Duty</span>
                <p class="text-2xl font-black text-slate-700 mt-1">{{ $stats['off_duty'] }}</p>
                <span class="text-[10px] text-slate-500">Off Shift</span>
            </div>

            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-rose-700 block">Conn Lost</span>
                <p class="text-2xl font-black text-rose-900 mt-1">{{ $stats['connection_lost'] }}</p>
                <span class="text-[10px] text-rose-700/80">&gt;2m Gap</span>
            </div>
        </div>

        {{-- Operatives Live Status Table --}}
        <div id="personnel-roster" class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Operatives Status Roster</h3>
                <span class="text-xs text-slate-500 font-mono">Updated: {{ now()->format('H:i:s') }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50/50 border-b border-slate-200 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                        <tr>
                            <th class="py-3 px-4">Operative</th>
                            <th class="py-3 px-4">Office &amp; Teams</th>
                            <th class="py-3 px-4">Account Presence</th>
                            <th class="py-3 px-4">Operational Status</th>
                            <th class="py-3 px-4">Last Heartbeat</th>
                            <th class="py-3 px-4">Status Reason</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($personnel as $p)
                            @php
                                $presence = $p->user?->userPresence?->status ?? 'offline';
                                $opStatus = $p->availability?->operational_status ?? 'off_duty';
                                $lastBeat = $p->user?->userPresence?->last_heartbeat_at;
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">{{ $p->user?->name }}</div>
                                    <div class="text-[11px] font-mono text-slate-500">#{{ $p->badge_number }} · {{ $p->rank_designation }}</div>
                                </td>

                                <td class="py-3 px-4">
                                    <div class="font-medium text-slate-800">{{ $p->office?->name ?? 'N/A' }}</div>
                                    <div class="flex flex-wrap gap-1 mt-0.5">
                                        @foreach($p->teams as $team)
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                {{ $team->call_sign }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>

                                <td class="py-3 px-4">
                                    @if($presence === 'online')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Online
                                        </span>
                                    @elseif($presence === 'connection_lost')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 animate-bounce">
                                            <span class="w-2 h-2 rounded-full bg-rose-500"></span> Lost
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                            <span class="w-2 h-2 rounded-full bg-slate-400"></span> Offline
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3 px-4">
                                    @if($opStatus === 'available')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Available
                                        </span>
                                    @elseif($opStatus === 'dispatched')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                            Dispatched
                                        </span>
                                    @elseif($opStatus === 'on_scene')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                            On Scene
                                        </span>
                                    @elseif($opStatus === 'busy')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            Busy
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600">
                                            Off Duty
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3 px-4 font-mono text-[11px] text-slate-600">
                                    {{ $lastBeat ? $lastBeat->diffForHumans() : 'Never' }}
                                </td>

                                <td class="py-3 px-4 text-slate-500 text-[11px]">
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
