<x-layouts.personnel>
    <div class="space-y-6">

        {{-- Top Bar & Title --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#E87524]/12 border border-[#E87524]/25 text-[#B94F12] dark:text-[#F29A56] text-[11px] font-bold uppercase tracking-wider mb-1">
                    <i data-lucide="file-text" class="w-3.5 h-3.5 text-[#E87524]"></i>
                    Operational Archive · Hinoba-an
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-[var(--text-primary)]">
                    Incident Response Records
                </h1>
                <p class="text-xs sm:text-sm text-[var(--text-secondary)] mt-1">
                    Complete history of emergency assignments, field deployments, and resolved operations.
                </p>
            </div>
        </div>

        {{-- Records Table --}}
        <div class="alerto-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[var(--surface-panel)] border-b border-[var(--border-subtle)] text-[var(--text-muted)] uppercase text-[10px] tracking-wider font-bold">
                        <tr>
                            <th class="py-3 px-4">Incident Reference</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4">Barangay</th>
                            <th class="py-3 px-4">Deployment Role</th>
                            <th class="py-3 px-4">Assigned At</th>
                            <th class="py-3 px-4">Mission Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border-subtle)]">
                        @forelse($assignments as $assignment)
                            <tr class="hover:bg-[var(--surface-hover)]/60 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold text-[var(--text-primary)]">
                                    {{ $assignment->incident?->reference_number ?? '#INC-' . $assignment->incident_id }}
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold bg-[var(--surface-panel)] border border-[var(--border-subtle)] text-[var(--text-secondary)]">
                                        {{ $assignment->incident?->category?->name ?? 'Emergency' }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 font-bold text-[var(--text-primary)]">
                                    {{ $assignment->incident?->barangay?->name ?? 'Hinoba-an' }}
                                </td>

                                <td class="py-3.5 px-4 text-[var(--text-secondary)]">
                                    @if($assignment->team)
                                        <span class="font-bold text-[#B94F12] dark:text-[#F29A56]">{{ $assignment->team->call_sign }}</span>
                                    @else
                                        <span>Direct Responder</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 font-mono text-[11px] text-[var(--text-muted)]">
                                    {{ $assignment->assigned_at ? $assignment->assigned_at->format('M d, Y H:i') : '—' }}
                                </td>

                                <td class="py-3.5 px-4">
                                    @if($assignment->status === 'active')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#E87524]"></span> Active Run
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#27845B]/15 text-[#1B6644] dark:text-[#5BD19A]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#27845B] dark:bg-[#5BD19A]"></span> Completed
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-[var(--text-muted)]">
                                    <i data-lucide="archive" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                    <p class="font-bold text-[var(--text-secondary)]">No response history records found.</p>
                                    <p class="text-xs text-[var(--text-muted)] mt-1">Completed incident missions will be catalogued here.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($assignments->hasPages())
                <div class="p-4 border-t border-[var(--border-subtle)] bg-[var(--surface-panel)]">
                    {{ $assignments->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layouts.personnel>
