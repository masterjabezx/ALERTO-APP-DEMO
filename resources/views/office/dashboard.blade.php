<x-layouts.office>
    <div class="space-y-6">

        {{-- Agency Header Card --}}
        <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-xs">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-[#173957] text-[#F0A82E] flex items-center justify-center font-bold text-xl shadow-md">
                        <i data-lucide="building-2" class="w-7 h-7"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">{{ $office->name }}</h1>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase {{ $officeUser?->office_level === 'office_admin' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $officeUser?->office_level === 'office_admin' ? 'Office Admin' : 'Office Staff' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium mt-1">
                            Agency Code: <span class="font-mono font-bold text-slate-700">{{ $office->code }}</span> · 
                            Station: <span class="font-bold text-slate-700">{{ $office->location?->barangay?->name ?? 'Poblacion' }}</span> · 
                            Hotline: <span class="font-mono text-slate-700">{{ $office->contact_phone }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('office.incidents') }}" 
                       class="px-4 py-2.5 rounded-xl bg-[#173957] hover:bg-[#1f4b72] text-white font-bold text-xs shadow-md transition-all flex items-center gap-1.5">
                        <i data-lucide="radio" class="w-4 h-4 text-[#3187D8]"></i>
                        <span>Monitor Incidents</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- KPI Quick Metrics Bar (Real Scoped DB Data) --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
            <div class="p-3.5 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Authorized</span>
                <p class="text-xl font-black text-slate-900 mt-1">{{ number_format($stats['total_authorized_incidents']) }}</p>
                <span class="text-[9px] text-slate-400">Total in scope</span>
            </div>

            <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200/80 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 block">Pending Review</span>
                <p class="text-xl font-black text-amber-900 mt-1">{{ number_format($stats['pending_review']) }}</p>
                <span class="text-[9px] text-amber-700/80">Requires intake</span>
            </div>

            <div class="p-3.5 rounded-2xl bg-blue-50 border border-blue-200/80 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-blue-700 block">Accepted</span>
                <p class="text-xl font-black text-blue-900 mt-1">{{ number_format($stats['accepted']) }}</p>
                <span class="text-[9px] text-blue-700/80">Queued for unit</span>
            </div>

            <div class="p-3.5 rounded-2xl bg-indigo-50 border border-indigo-200/80 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-700 block">Assigned</span>
                <p class="text-xl font-black text-indigo-900 mt-1">{{ number_format($stats['assigned']) }}</p>
                <span class="text-[9px] text-indigo-700/80">Unit linked</span>
            </div>

            <div class="p-3.5 rounded-2xl bg-purple-50 border border-purple-200/80 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700 block">Dispatched</span>
                <p class="text-xl font-black text-purple-900 mt-1">{{ number_format($stats['dispatched']) }}</p>
                <span class="text-[9px] text-purple-700/80">Mobilized</span>
            </div>

            <div class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200/80 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-rose-700 block">Active Scene</span>
                <p class="text-xl font-black text-rose-900 mt-1">{{ number_format($stats['active_responses']) }}</p>
                <span class="text-[9px] text-rose-700/80">Units operating</span>
            </div>

            <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200/80 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 block">Resolved</span>
                <p class="text-xl font-black text-emerald-900 mt-1">{{ number_format($stats['resolved']) }}</p>
                <span class="text-[9px] text-emerald-700/80">Completed runs</span>
            </div>

            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-600 block">Online Staff</span>
                <p class="text-xl font-black text-slate-800 mt-1">{{ number_format($stats['online_personnel']) }}</p>
                <span class="text-[9px] text-slate-500">of {{ $stats['total_personnel'] }} operatives</span>
            </div>
        </div>

        {{-- Main Dashboard Two-Column Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Recent Incidents Feed (2 Columns) --}}
            <div class="lg:col-span-2 bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <i data-lucide="radio" class="w-4 h-4 text-[#3187D8]"></i>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Recent Agency Incidents</h2>
                    </div>
                    <a href="{{ route('office.incidents') }}" class="text-xs font-bold text-[#3187D8] hover:underline">
                        View All Monitoring &rarr;
                    </a>
                </div>

                <div class="space-y-3">
                    @forelse($recentIncidents as $incident)
                        <div class="p-4 rounded-xl border border-slate-200 hover:border-[#3187D8]/60 transition-all bg-slate-50/50">
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-[#173957] text-white">
                                        {{ $incident->reference_number }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-rose-100 text-rose-800">
                                        {{ $incident->category?->name }}
                                    </span>
                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold uppercase {{ $incident->priority === 'critical' ? 'bg-red-500 text-white' : 'bg-amber-100 text-amber-800' }}">
                                        {{ ucfirst($incident->priority) }}
                                    </span>
                                </div>
                                <span class="text-[11px] text-slate-400 font-mono">
                                    {{ $incident->submitted_at ? $incident->submitted_at->diffForHumans() : '' }}
                                </span>
                            </div>

                            <p class="text-xs text-slate-700 line-clamp-2 mt-1 mb-2">
                                {{ $incident->description }}
                            </p>

                            <div class="flex items-center justify-between pt-2 border-t border-slate-200/60 text-xs">
                                <div class="text-slate-500">
                                    Barangay: <span class="font-bold text-slate-800">{{ $incident->barangay?->name }}</span>
                                </div>
                                <a href="{{ route('office.incidents.show', $incident->reference_number) }}" 
                                   class="font-bold text-[#3187D8] hover:underline">
                                    Inspect Incident &rarr;
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-slate-400">
                            <i data-lucide="check-circle" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                            <p class="font-bold text-slate-600">No recent incidents in your agency scope.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Agency Units & Online Responders (1 Column) --}}
            <div class="space-y-6">

                {{-- Response Teams Widget --}}
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <i data-lucide="shield" class="w-4 h-4 text-[#3187D8]"></i>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Assigned Teams</h3>
                        </div>
                        <span class="text-xs font-bold text-slate-500">{{ $teams->count() }} Team(s)</span>
                    </div>

                    <div class="space-y-2">
                        @forelse($teams as $team)
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                                <div>
                                    <div class="font-bold text-xs text-slate-900">{{ $team->team_name }}</div>
                                    <div class="text-[10px] text-slate-500 font-mono">Call Sign: {{ $team->call_sign }}</div>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800">
                                    {{ $team->members->count() }} Operatives
                                </span>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 italic">No response teams assigned to this office.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Online Field Responders --}}
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <i data-lucide="activity" class="w-4 h-4 text-emerald-600"></i>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Online Operatives</h3>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                            {{ $onlinePersonnel->count() }} Active
                        </span>
                    </div>

                    <div class="space-y-2 max-h-56 overflow-y-auto">
                        @forelse($onlinePersonnel as $p)
                            <div class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-50">
                                <div>
                                    <div class="text-xs font-bold text-slate-800">{{ $p->user?->name }}</div>
                                    <div class="text-[10px] font-mono text-slate-400">#{{ $p->badge_number }} · {{ $p->rank_designation }}</div>
                                </div>
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Online
                                </span>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 italic">No operatives currently logged in.</p>
                        @endforelse
                    </div>
                </div>

            </div>

        </div>

    </div>
</x-layouts.office>