<x-layouts.admin title="Incoming Emergency Reports — DIAL DAPH">
    @php
        $incomingRoute = auth()->user()?->isSuperAdmin() ? 'super-admin.incoming.index' : 'admin.incoming.index';
    @endphp
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <x-portal.page-header
                badge="DIAL DAPH Call Intake Queue"
                title="Incoming Citizen &amp; Guest Reports"
                subtitle="Reports submitted via simplified intake awaiting DIAL DAPH call verification and emergency assessment."
            />
            <div class="flex flex-wrap gap-2 text-xs shrink-0">
                @foreach(['all' => 'All Unclassified', 'new_report' => 'New Reports', 'awaiting_call' => 'Awaiting Call', 'assessment_in_progress' => 'Assessment In Progress'] as $key => $label)
                    <a href="{{ route($incomingRoute, ['stage' => $key]) }}"
                       class="px-3 py-1.5 rounded-xl font-bold transition-colors {{ $stage === $key ? 'bg-[#E87524] text-white shadow-xs' : 'alerto-card text-[#77736E] hover:text-[#E87524]' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="alerto-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F5F3EF] dark:bg-white/5 border-b border-[#E6E3DE] dark:border-white/10 text-[10px] uppercase tracking-wider text-[#77736E] font-bold">
                        <tr>
                            <th class="p-4">Reference &amp; Channel</th>
                            <th class="p-4">Caller Name &amp; Phone</th>
                            <th class="p-4">Coordinates / Verified Barangay</th>
                            <th class="p-4">Workflow Stage</th>
                            <th class="p-4">Operator Lock</th>
                            <th class="p-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E6E3DE] dark:divide-white/10">
                        @forelse($reports as $rep)
                            <tr class="hover:bg-[#FFF0E4]/40 dark:hover:bg-white/5 transition-colors">
                                <td class="p-4">
                                    <span class="font-mono font-black block text-[#B94F12] dark:text-[#F29A56]">{{ $rep->reference_number }}</span>
                                    <span class="text-[10px] text-[#77736E] uppercase">{{ $rep->is_guest_report ? 'GUEST REPORT' : 'CITIZEN REPORT' }} · {{ $rep->submitted_at?->diffForHumans() }}</span>
                                </td>
                                <td class="p-4">
                                    <div class="font-bold">{{ $rep->caller_name }}</div>
                                    <div class="font-mono text-[#27845B] font-semibold">{{ $rep->caller_phone }}</div>
                                </td>
                                <td class="p-4">
                                    <span class="font-bold block">{{ $rep->barangay?->name ?? 'Unverified Polygon Area' }}</span>
                                    @if($rep->location)
                                        <span class="font-mono text-[10px] text-[#77736E]">{{ number_format((float)$rep->location->latitude, 5) }}, {{ number_format((float)$rep->location->longitude, 5) }}</span>
                                    @endif
                                </td>
                                <td class="p-4">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56] border border-[#E87524]/30">
                                        {{ str_replace('_', ' ', $rep->workflow_stage ?? 'new_report') }}
                                    </span>
                                </td>
                                <td class="p-4 text-[11px] text-[#77736E]">
                                    {{ $rep->processedByAdmin?->name ?? 'Unclaimed' }}
                                </td>
                                <td class="p-4 text-right">
                                    <a href="{{ route('admin.incidents.show', $rep->reference_number) }}"
                                       class="alerto-btn-press inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#27845B] hover:bg-[#1f6b49] text-white font-bold shadow-xs transition-colors">
                                        Open &amp; Call Citizen
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-[#77736E]">No incoming unclassified reports in queue.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-[#E6E3DE] dark:border-white/10">{{ $reports->links() }}</div>
        </div>
    </div>
</x-layouts.admin>
