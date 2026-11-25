<x-layouts.personnel>
    <div class="space-y-6">

        {{-- Top Bar & Title --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#173957]/10 text-[#173957] text-[11px] font-bold uppercase tracking-wider mb-1">
                    <i data-lucide="file-text" class="w-3.5 h-3.5 text-[#3187D8]"></i>
                    Operational Archive · Hinoba-an
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                    Incident Response Records
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Complete history of emergency assignments, field deployments, and resolved operations.
                </p>
            </div>
        </div>

        {{-- Records Table --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-[10px] tracking-wider font-bold">
                        <tr>
                            <th class="py-3 px-4">Incident Reference</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4">Barangay</th>
                            <th class="py-3 px-4">Deployment Role</th>
                            <th class="py-3 px-4">Assigned At</th>
                            <th class="py-3 px-4">Mission Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($assignments as $assignment)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                    {{ $assignment->incident?->incident_number ?? '#INC-' . $assignment->incident_id }}
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700">
                                        {{ $assignment->incident?->category?->name ?? 'Emergency' }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 font-bold text-slate-800">
                                    {{ $assignment->incident?->barangay?->name ?? 'Hinoba-an' }}
                                </td>

                                <td class="py-3.5 px-4 text-slate-600">
                                    @if($assignment->team)
                                        <span class="font-bold text-[#173957]">{{ $assignment->team->call_sign }}</span>
                                    @else
                                        <span>Direct Responder</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500">
                                    {{ $assignment->assigned_at ? $assignment->assigned_at->format('M d, Y H:i') : '—' }}
                                </td>

                                <td class="py-3.5 px-4">
                                    @if($assignment->status === 'active')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span> Active Run
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> Completed
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    <i data-lucide="archive" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                    <p class="font-bold text-slate-600">No response history records found.</p>
                                    <p class="text-xs text-slate-400 mt-1">Completed incident missions will be catalogued here.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($assignments->hasPages())
                <div class="p-4 border-t border-slate-200 bg-slate-50">
                    {{ $assignments->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layouts.personnel>
