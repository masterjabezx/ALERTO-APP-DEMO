<x-layouts.personnel>
    <div class="space-y-6">

        {{-- Top Bar & Title --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#173957]/10 text-[#173957] text-[11px] font-bold uppercase tracking-wider mb-1">
                    <i data-lucide="radio" class="w-3.5 h-3.5 text-[#3187D8]"></i>
                    Jurisdiction Incidents · Hinoba-an
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                    Active Emergency Incidents
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Live emergency events within your authorized operational territory and response jurisdiction.
                </p>
            </div>
        </div>

        {{-- Incidents List --}}
        <div class="space-y-4">
            @forelse($incidents as $incident)
                <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-xs hover:border-[#3187D8]/60 transition-all">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded-md font-mono text-xs font-bold bg-[#173957] text-white">
                                {{ $incident->reference_number }}
                            </span>
                            <span class="px-2.5 py-1 rounded-md text-xs font-bold uppercase bg-rose-100 text-rose-800">
                                {{ $incident->category?->name ?? 'Emergency' }}
                            </span>
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold uppercase {{ $incident->priority === 'critical' ? 'bg-red-500 text-white' : 'bg-amber-100 text-amber-800' }}">
                                {{ ucfirst($incident->priority) }}
                            </span>
                        </div>
                        <span class="text-xs text-slate-400 font-mono">
                            Reported: {{ $incident->submitted_at ? $incident->submitted_at->diffForHumans() : '' }}
                        </span>
                    </div>

                    <div class="my-3">
                        <h3 class="text-base font-bold text-slate-900">{{ $incident->title ?? ($incident->category?->name ?? 'Emergency Incident') }}</h3>
                        <p class="text-xs text-slate-600 mt-1">{{ $incident->description }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 border-t border-slate-100 text-xs">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Barangay</span>
                            <span class="font-bold text-slate-800">{{ $incident->barangay?->name }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Scene Coordinates</span>
                            <span class="font-mono text-slate-700">
                                @if($incident->location)
                                    {{ number_format($incident->location->latitude, 4) }}, {{ number_format($incident->location->longitude, 4) }}
                                @else
                                    Central Coordinates
                                @endif
                            </span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Dispatched Units</span>
                            <span class="font-bold text-[#173957]">
                                {{ $incident->activeAssignments->count() }} Unit(s) Mobilized
                            </span>
                        </div>
                    </div>

                    <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <a href="{{ route('personnel.messages.incident', $incident->reference_number) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#173957] hover:bg-[#122e46] text-white text-xs font-bold transition-colors shadow-xs">
                            <i data-lucide="message-square" class="w-3.5 h-3.5 text-[#3187D8]"></i>
                            Message Admin About This Incident
                        </a>
                    </div>
                </div>
            @empty
                <div class="py-16 text-center text-slate-400 bg-white rounded-2xl border border-slate-200">
                    <i data-lucide="shield-check" class="w-10 h-10 mx-auto mb-2 text-emerald-500"></i>
                    <p class="font-bold text-slate-700 text-sm">No Active Emergency Incidents in Your Territory</p>
                    <p class="text-xs text-slate-400 mt-1">All incidents in your authorized Hinoba-an barangays are clear.</p>
                </div>
            @endforelse
        </div>

        @if($incidents->hasPages())
            <div class="p-4 bg-white rounded-2xl border border-slate-200">
                {{ $incidents->links() }}
            </div>
        @endif

    </div>
</x-layouts.personnel>
