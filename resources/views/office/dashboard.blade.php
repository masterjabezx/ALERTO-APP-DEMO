<x-layouts.office>
    <div class="space-y-6">

        {{-- Agency Header Card --}}
        <div class="alerto-card p-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-44 h-44 bg-[#E87524]/8 rounded-full blur-3xl pointer-events-none"></div>
            <div class="relative flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-[#17191C] text-[#E87524] border border-white/10 flex items-center justify-center font-bold text-xl shadow-md shrink-0">
                        <i data-lucide="building-2" class="w-7 h-7"></i>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-xl sm:text-2xl font-black text-[var(--text-primary)] tracking-tight">{{ $office->name }}</h1>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase {{ $officeUser?->office_level === 'office_admin' ? 'bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56] border border-[#E87524]/30' : 'bg-[#397BA8]/15 text-[#25597E] dark:text-[#7BB5DB] border border-[#397BA8]/30' }}">
                                {{ $officeUser?->office_level === 'office_admin' ? 'Office Admin' : 'Office Staff' }}
                            </span>
                        </div>
                        <p class="text-xs text-[var(--text-secondary)] font-medium mt-1">
                            Agency Code: <span class="font-mono font-bold text-[var(--text-primary)]">{{ $office->code }}</span> · 
                            Station: <span class="font-bold text-[var(--text-primary)]">{{ $office->location?->barangay?->name ?? 'Poblacion' }}</span> · 
                            Hotline: <span class="font-mono text-[var(--text-primary)]">{{ $office->contact_phone }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('office.incidents') }}" 
                       class="px-4 py-2.5 rounded-xl bg-[#E87524] hover:bg-[#D16419] text-white font-bold text-xs shadow-md shadow-[#E87524]/25 transition-all flex items-center gap-1.5">
                        <i data-lucide="radio" class="w-4 h-4"></i>
                        <span>Monitor Incidents</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- KPI Quick Metrics Bar (Real Scoped DB Data) --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
            <div class="alerto-card p-3.5">
                <span class="text-[10px] font-bold uppercase tracking-wider text-[var(--text-muted)] block">Authorized</span>
                <p class="text-xl font-black text-[var(--text-primary)] font-mono mt-1">{{ number_format($stats['total_authorized_incidents']) }}</p>
                <span class="text-[9px] text-[var(--text-muted)]">Total in scope</span>
            </div>

            <div class="p-3.5 rounded-2xl bg-[#D99024]/10 border border-[#D99024]/30 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#9A5D07] dark:text-[#F6BA53] block">Pending Review</span>
                <p class="text-xl font-black text-[var(--text-primary)] font-mono mt-1">{{ number_format($stats['pending_review']) }}</p>
                <span class="text-[9px] text-[#9A5D07]/90 dark:text-[#F6BA53]/80">Requires intake</span>
            </div>

            <div class="p-3.5 rounded-2xl bg-[#397BA8]/10 border border-[#397BA8]/30 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#25597E] dark:text-[#7BB5DB] block">Accepted</span>
                <p class="text-xl font-black text-[var(--text-primary)] font-mono mt-1">{{ number_format($stats['accepted']) }}</p>
                <span class="text-[9px] text-[#25597E]/90 dark:text-[#7BB5DB]/80">Queued for unit</span>
            </div>

            <div class="p-3.5 rounded-2xl bg-[#E87524]/10 border border-[#E87524]/30 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#B94F12] dark:text-[#F29A56] block">Assigned</span>
                <p class="text-xl font-black text-[var(--text-primary)] font-mono mt-1">{{ number_format($stats['assigned']) }}</p>
                <span class="text-[9px] text-[#B94F12]/90 dark:text-[#F29A56]/80">Unit linked</span>
            </div>

            <div class="p-3.5 rounded-2xl bg-[#E87524]/15 border border-[#E87524]/40 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#9A400D] dark:text-[#F5B076] block">Dispatched</span>
                <p class="text-xl font-black text-[var(--text-primary)] font-mono mt-1">{{ number_format($stats['dispatched']) }}</p>
                <span class="text-[9px] text-[#9A400D]/90 dark:text-[#F5B076]/80">Mobilized</span>
            </div>

            <div class="p-3.5 rounded-2xl bg-[#C83F43]/10 border border-[#C83F43]/30 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#9F292D] dark:text-[#F27D80] block">Active Scene</span>
                <p class="text-xl font-black text-[var(--text-primary)] font-mono mt-1">{{ number_format($stats['active_responses']) }}</p>
                <span class="text-[9px] text-[#9F292D]/90 dark:text-[#F27D80]/80">Units operating</span>
            </div>

            <div class="p-3.5 rounded-2xl bg-[#27845B]/10 border border-[#27845B]/30 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#1B6644] dark:text-[#5BD19A] block">Resolved</span>
                <p class="text-xl font-black text-[var(--text-primary)] font-mono mt-1">{{ number_format($stats['resolved']) }}</p>
                <span class="text-[9px] text-[#1B6644]/90 dark:text-[#5BD19A]/80">Completed runs</span>
            </div>

            <div class="alerto-card p-3.5 bg-[var(--surface-panel)]">
                <span class="text-[10px] font-bold uppercase tracking-wider text-[var(--text-muted)] block">Online Staff</span>
                <p class="text-xl font-black text-[var(--text-primary)] font-mono mt-1">{{ number_format($stats['online_personnel']) }}</p>
                <span class="text-[9px] text-[var(--text-muted)]">of {{ $stats['total_personnel'] }} operatives</span>
            </div>
        </div>

        {{-- Main Dashboard Two-Column Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Recent Incidents Feed (2 Columns) --}}
            <div class="lg:col-span-2 alto alerto-card p-6">
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-[var(--border-subtle)]">
                    <div class="flex items-center gap-2">
                        <i data-lucide="radio" class="w-4 h-4 text-[#E87524]"></i>
                        <h2 class="text-sm font-bold text-[var(--text-primary)] uppercase tracking-wider">Recent Agency Incidents</h2>
                    </div>
                    <a href="{{ route('office.incidents') }}" class="text-xs font-bold text-[#B94F12] dark:text-[#F29A56] hover:underline">
                        View All Monitoring &rarr;
                    </a>
                </div>

                <div class="space-y-3">
                    @forelse($recentIncidents as $incident)
                        <div class="p-4 rounded-xl border border-[var(--border-subtle)] hover:border-[#E87524]/50 transition-all bg-[var(--surface-panel)]/70">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-[#17191C] text-[#F29A56]">
                                        {{ $incident->reference_number }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-[#C83F43]/12 text-[#9F292D] dark:text-[#F27D80]">
                                        {{ $incident->category?->name }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $incident->priority === 'critical' ? 'bg-[#C83F43] text-white' : 'bg-[#D99024]/15 text-[#9A5D07] dark:text-[#F6BA53]' }}">
                                        {{ ucfirst($incident->priority) }}
                                    </span>
                                </div>
                                <span class="text-[11px] text-[var(--text-muted)] font-mono">
                                    {{ $incident->submitted_at ? $incident->submitted_at->diffForHumans() : '' }}
                                </span>
                            </div>

                            <p class="text-xs text-[var(--text-secondary)] line-clamp-2 mt-1 mb-2">
                                {{ $incident->description }}
                            </p>

                            <div class="flex items-center justify-between pt-2 border-t border-[var(--border-subtle)] text-xs">
                                <div class="text-[var(--text-muted)]">
                                    Barangay: <span class="font-bold text-[var(--text-primary)]">{{ $incident->barangay?->name }}</span>
                                </div>
                                <a href="{{ route('office.incidents.show', $incident->reference_number) }}" 
                                   class="font-bold text-[#B94F12] dark:text-[#F29A56] hover:underline">
                                    Inspect Incident &rarr;
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-[var(--text-muted)]">
                            <i data-lucide="check-circle" class="w-8 h-8 mx-auto mb-2 opacity-50 text-[#27845B]"></i>
                            <p class="font-bold text-[var(--text-secondary)]">No recent incidents in your agency scope.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Agency Units & Online Responders (1 Column) --}}
            <div class="space-y-6">

                {{-- Response Teams Widget --}}
                <div class="alerto-card p-6">
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-[var(--border-subtle)]">
                        <div class="flex items-center gap-2">
                            <i data-lucide="shield" class="w-4 h-4 text-[#E87524]"></i>
                            <h3 class="text-xs font-bold text-[var(--text-primary)] uppercase tracking-wider">Assigned Teams</h3>
                        </div>
                        <span class="text-xs font-bold text-[var(--text-muted)]">{{ $teams->count() }} Team(s)</span>
                    </div>

                    <div class="space-y-2">
                        @forelse($teams as $team)
                            <div class="p-3 rounded-xl bg-[var(--surface-panel)] border border-[var(--border-subtle)] flex items-center justify-between gap-2">
                                <div>
                                    <div class="font-bold text-xs text-[var(--text-primary)]">{{ $team->team_name }}</div>
                                    <div class="text-[10px] text-[var(--text-muted)] font-mono">Call Sign: {{ $team->call_sign }}</div>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56]">
                                    {{ $team->members->count() }} Operatives
                                </span>
                            </div>
                        @empty
                            <p class="text-xs text-[var(--text-muted)] italic">No response teams assigned to this office.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Online Field Responders --}}
                <div class="alerto-card p-6">
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-[var(--border-subtle)]">
                        <div class="flex items-center gap-2">
                            <i data-lucide="activity" class="w-4 h-4 text-[#27845B] dark:text-[#5BD19A]"></i>
                            <h3 class="text-xs font-bold text-[var(--text-primary)] uppercase tracking-wider">Online Operatives</h3>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#27845B]/15 text-[#1B6644] dark:text-[#5BD19A]">
                            {{ $onlinePersonnel->count() }} Active
                        </span>
                    </div>

                    <div class="space-y-2 max-h-56 overflow-y-auto alerto-scrollbar">
                        @forelse($onlinePersonnel as $p)
                            <div class="flex items-center justify-between p-2 rounded-lg hover:bg-[var(--surface-hover)]/60 transition-colors">
                                <div>
                                    <div class="text-xs font-bold text-[var(--text-primary)]">{{ $p->user?->name }}</div>
                                    <div class="text-[10px] font-mono text-[var(--text-muted)]">#{{ $p->badge_number }} · {{ $p->rank_designation }}</div>
                                </div>
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-[#27845B] dark:text-[#5BD19A]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#27845B] dark:bg-[#5BD19A] animate-pulse"></span> Online
                                </span>
                            </div>
                        @empty
                            <p class="text-xs text-[var(--text-muted)] italic">No operatives currently logged in.</p>
                        @endforelse
                    </div>
                </div>

            </div>

        </div>

    </div>
</x-layouts.office>
