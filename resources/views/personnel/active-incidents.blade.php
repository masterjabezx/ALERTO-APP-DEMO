<x-layouts.personnel>
    <div class="space-y-6">

        {{-- Top Bar & Title --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#E87524]/12 border border-[#E87524]/25 text-[#B94F12] dark:text-[#F29A56] text-[11px] font-bold uppercase tracking-wider mb-1">
                    <i data-lucide="radio" class="w-3.5 h-3.5 text-[#E87524]"></i>
                    Jurisdiction Incidents · Hinoba-an
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-[var(--text-primary)]">
                    Active Emergency Incidents
                </h1>
                <p class="text-xs sm:text-sm text-[var(--text-secondary)] mt-1">
                    Live emergency events within your authorized operational territory and response jurisdiction.
                </p>
            </div>
        </div>

        {{-- Incidents List --}}
        <div class="space-y-4">
            @forelse($incidents as $incident)
                <div class="alerto-card p-6 hover:border-[#E87524]/55 transition-all">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[var(--border-subtle)]">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2.5 py-1 rounded-md font-mono text-xs font-bold bg-[#17191C] text-[#F29A56]">
                                {{ $incident->reference_number }}
                            </span>
                            <span class="px-2.5 py-1 rounded-md text-xs font-bold uppercase bg-[#C83F43]/12 text-[#9F292D] dark:text-[#F27D80]">
                                {{ $incident->category?->name ?? 'Emergency' }}
                            </span>
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold uppercase {{ $incident->priority === 'critical' ? 'bg-[#C83F43] text-white' : 'bg-[#D99024]/15 text-[#9A5D07] dark:text-[#F6BA53]' }}">
                                {{ ucfirst($incident->priority) }}
                            </span>
                        </div>
                        <span class="text-xs text-[var(--text-muted)] font-mono">
                            Reported: {{ $incident->submitted_at ? $incident->submitted_at->diffForHumans() : '' }}
                        </span>
                    </div>

                    <div class="my-3">
                        <h3 class="text-base font-bold text-[var(--text-primary)]">{{ $incident->title ?? ($incident->category?->name ?? 'Emergency Incident') }}</h3>
                        <p class="text-xs text-[var(--text-secondary)] mt-1">{{ $incident->description }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 border-t border-[var(--border-subtle)] text-xs">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-[var(--text-muted)] block">Barangay</span>
                            <span class="font-bold text-[var(--text-primary)]">{{ $incident->barangay?->name }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-[var(--text-muted)] block">Scene Coordinates</span>
                            <span class="font-mono text-[var(--text-secondary)]">
                                @if($incident->location)
                                    {{ number_format($incident->location->latitude, 4) }}, {{ number_format($incident->location->longitude, 4) }}
                                @else
                                    Central Coordinates
                                @endif
                            </span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-[var(--text-muted)] block">Dispatched Units</span>
                            <span class="font-bold text-[#B94F12] dark:text-[#F29A56]">
                                {{ $incident->activeAssignments->count() }} Unit(s) Mobilized
                            </span>
                        </div>
                    </div>

                    <div class="mt-3 pt-3 border-t border-[var(--border-subtle)] flex items-center justify-end gap-2">
                        <a href="{{ route('personnel.messages.incident', $incident->reference_number) }}"
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#17191C] hover:bg-[#292D32] text-white text-xs font-bold transition-colors shadow-xs border border-white/10">
                            <i data-lucide="message-square" class="w-3.5 h-3.5 text-[#E87524]"></i>
                            Message Admin About This Incident
                        </a>
                    </div>
                </div>
            @empty
                <div class="alerto-card py-16 text-center text-[var(--text-muted)]">
                    <i data-lucide="shield-check" class="w-10 h-10 mx-auto mb-2 text-[#27845B] dark:text-[#5BD19A]"></i>
                    <p class="font-bold text-[var(--text-primary)] text-sm">No Active Emergency Incidents in Your Territory</p>
                    <p class="text-xs text-[var(--text-muted)] mt-1">All incidents in your authorized Hinoba-an barangays are clear.</p>
                </div>
            @endforelse
        </div>

        @if($incidents->hasPages())
            <div class="alerto-card p-4">
                {{ $incidents->links() }}
            </div>
        @endif

    </div>
</x-layouts.personnel>
