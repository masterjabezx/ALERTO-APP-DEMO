<x-layouts.admin title="Dispatch Console — DIAL DAPH Command Center">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-black tracking-tight">Emergency Response Dispatch Console</h1>
            <p class="text-xs text-slate-400 mt-1">Monitor classified incidents awaiting dispatch, deployment warnings, and active mobilized teams.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="eoc-card p-5 space-y-3">
                <h2 class="text-xs font-black uppercase tracking-wider text-amber-400">Accepted / Deployment Warnings ({{ $awaitingDispatch->count() }})</h2>
                @forelse($awaitingDispatch as $inc)
                    <div class="p-3.5 rounded-xl bg-white/5 border {{ $inc->has_assignment_warning ? 'border-amber-500/60' : 'border-white/10' }} text-xs space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-mono font-black text-white">{{ $inc->reference_number }}</span>
                            <a href="{{ route('admin.incidents.show', $inc->reference_number) }}" class="text-cyan-300 font-bold hover:underline">Manage Dispatch &rarr;</a>
                        </div>
                        <p class="text-slate-300">{{ $inc->category?->name ?? 'Unclassified' }} · {{ $inc->barangay?->name ?? 'Hinoba-an' }} · Priority: {{ strtoupper($inc->priority ?? 'unassigned') }}</p>
                        @if($inc->has_assignment_warning)
                            <p class="text-amber-300 font-bold">Warning: {{ $inc->assignment_warning_message }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-slate-400">No accepted incidents pending deployment.</p>
                @endforelse
            </div>

            <div class="eoc-card p-5 space-y-3">
                <h2 class="text-xs font-black uppercase tracking-wider text-emerald-400">Active Unit Deployments ({{ $activeAssignments->total() }})</h2>
                @forelse($activeAssignments as $asg)
                    <div class="p-3.5 rounded-xl bg-white/5 border border-white/10 text-xs flex items-center justify-between">
                        <div>
                            <span class="font-bold text-white block">{{ $asg->office?->name }} {{ $asg->team ? "· {$asg->team->team_name}" : '' }}</span>
                            <span class="text-slate-400">{{ $asg->personnel?->user?->name ?? 'Office Pool' }} · {{ $asg->incident?->reference_number }}</span>
                        </div>
                        @if($asg->incident?->reference_number)
                            <a href="{{ route('admin.incidents.show', $asg->incident->reference_number) }}" class="px-2.5 py-1 rounded bg-white/10 text-white font-bold">Inspect</a>
                        @else
                            <span class="px-2.5 py-1 rounded bg-white/5 text-slate-500 font-bold">Unavailable</span>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-slate-400">No active unit assignments.</p>
                @endforelse
                {{ $activeAssignments->links() }}
            </div>
        </div>
    </div>
</x-layouts.admin>

