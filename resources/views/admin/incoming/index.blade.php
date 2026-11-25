<x-layouts.admin title="Incoming Unclassified Reports — DIAL DAPH Command Center">
    @php
        $incomingRoute = auth()->user()?->isSuperAdmin() ? 'super-admin.incoming.index' : 'admin.incoming.index';
    @endphp
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight">Incoming Citizen &amp; Guest Reports</h1>
                <p class="text-xs text-slate-400 mt-1">Reports submitted via simplified intake awaiting DIAL DAPH call verification and emergency assessment.</p>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                @foreach(['all' => 'All Unclassified', 'new_report' => 'New Reports', 'awaiting_call' => 'Awaiting Call', 'assessment_in_progress' => 'Assessment In Progress'] as $key => $label)
                    <a href="{{ route($incomingRoute, ['stage' => $key]) }}"
                       class="px-3 py-1.5 rounded-xl font-bold {{ $stage === $key ? 'bg-amber-500 text-slate-950' : 'eoc-card border border-white/10 text-slate-300' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="eoc-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-white/10 text-[10px] uppercase text-slate-400">
                        <tr>
                            <th class="p-4">Reference &amp; Channel</th>
                            <th class="p-4">Caller Name &amp; Phone</th>
                            <th class="p-4">Coordinates / Verified Barangay</th>
                            <th class="p-4">Workflow Stage</th>
                            <th class="p-4">Operator Lock</th>
                            <th class="p-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse($reports as $rep)
                            <tr class="hover:bg-white/5">
                                <td class="p-4">
                                    <span class="font-mono font-black text-white block">{{ $rep->reference_number }}</span>
                                    <span class="text-[10px] text-slate-400 uppercase">{{ $rep->is_guest_report ? 'GUEST REPORT' : 'CITIZEN REPORT' }} · {{ $rep->submitted_at?->diffForHumans() }}</span>
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-white">{{ $rep->caller_name }}</div>
                                    <div class="font-mono text-cyan-300">{{ $rep->caller_phone }}</div>
                                </td>
                                <td class="p-4">
                                    <span class="font-bold block">{{ $rep->barangay?->name ?? 'Unverified Polygon Area' }}</span>
                                    @if($rep->location)
                                        <span class="font-mono text-[10px] text-slate-400">{{ number_format((float)$rep->location->latitude, 5) }}, {{ number_format((float)$rep->location->longitude, 5) }}</span>
                                    @endif
                                </td>
                                <td class="p-4">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-amber-500/20 text-amber-300 border border-amber-500/40">
                                        {{ str_replace('_', ' ', $rep->workflow_stage ?? 'new_report') }}
                                    </span>
                                </td>
                                <td class="p-4 text-[11px] text-slate-300">
                                    {{ $rep->processedByAdmin?->name ?? 'Unclaimed' }}
                                </td>
                                <td class="p-4 text-right">
                                    <a href="{{ route('admin.incidents.show', $rep->reference_number) }}"
                                       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold">
                                        Open &amp; Call Citizen
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400">No incoming unclassified reports in queue.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-white/10">{{ $reports->links() }}</div>
        </div>
    </div>
</x-layouts.admin>

