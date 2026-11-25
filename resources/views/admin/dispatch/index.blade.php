<x-layouts.admin title="Dispatch Console — DIAL DAPH">
    <div class="space-y-6">
        <x-portal.page-header
            badge="Deployment Coordination · DIAL DAPH"
            title="Emergency Response Dispatch Console"
            subtitle="Monitor classified incidents awaiting dispatch, deployment warnings, and active mobilized teams."
        />

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="alerto-card p-5 space-y-3">
                <h2 class="text-xs font-black uppercase tracking-wider text-[#B94F12] dark:text-[#F29A56] flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-[#E87524]"></i>
                    Accepted / Deployment Warnings ({{ $awaitingDispatch->count() }})
                </h2>
                @forelse($awaitingDispatch as $inc)
                    <div class="p-3.5 rounded-xl bg-[#F5F3EF] dark:bg-white/5 border {{ $inc->has_assignment_warning ? 'border-[#D99024]/60' : 'border-[#E6E3DE] dark:border-white/10' }} text-xs space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-mono font-black">{{ $inc->reference_number }}</span>
                            <a href="{{ route('admin.incidents.show', $inc->reference_number) }}" class="text-[#B94F12] dark:text-[#F29A56] font-bold hover:underline">Manage Dispatch &rarr;</a>
                        </div>
                        <p class="text-[#77736E]">{{ $inc->category?->name ?? 'Unclassified' }} · {{ $inc->barangay?->name ?? 'Hinoba-an' }} · Priority: {{ strtoupper($inc->priority ?? 'unassigned') }}</p>
                        @if($inc->has_assignment_warning)
                            <p class="text-[#D99024] font-bold">Warning: {{ $inc->assignment_warning_message }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-[#77736E]">No accepted incidents pending deployment.</p>
                @endforelse
            </div>

            <div class="alerto-card p-5 space-y-3">
                <h2 class="text-xs font-black uppercase tracking-wider text-[#27845B] flex items-center gap-2">
                    <i data-lucide="send" class="w-4 h-4 text-[#27845B]"></i>
                    Active Unit Deployments ({{ $activeAssignments->total() }})
                </h2>
                @forelse($activeAssignments as $asg)
                    <div class="p-3.5 rounded-xl bg-[#F5F3EF] dark:bg-white/5 border border-[#E6E3DE] dark:border-white/10 text-xs flex items-center justify-between gap-3">
                        <div>
                            <span class="font-bold block">{{ $asg->office?->name }} {{ $asg->team ? "· {$asg->team->team_name}" : '' }}</span>
                            <span class="text-[#77736E]">{{ $asg->personnel?->user?->name ?? 'Office Pool' }} · {{ $asg->incident?->reference_number }}</span>
                        </div>
                        @if($asg->incident?->reference_number)
                            <a href="{{ route('admin.incidents.show', $asg->incident->reference_number) }}" class="px-2.5 py-1 rounded-lg bg-[#E87524] hover:bg-[#B94F12] text-white font-bold transition-colors">Inspect</a>
                        @else
                            <span class="px-2.5 py-1 rounded-lg bg-black/5 dark:bg-white/10 text-[#77736E] font-bold">Unavailable</span>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-[#77736E]">No active unit assignments.</p>
                @endforelse
                {{ $activeAssignments->links() }}
            </div>
        </div>
    </div>
</x-layouts.admin>
