<x-layouts.admin>
    <div class="space-y-6">

        {{-- Top Bar & Title --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#173957]/10 text-[#173957] text-[11px] font-bold uppercase tracking-wider mb-1">
                    <i data-lucide="radio" class="w-3.5 h-3.5 text-[#3187D8]"></i>
                    Operational Missions · Hinoba-an
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                    Active Personnel Assignments
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Live tracking of field deployments, tactical response units on scene, and elapsed mission durations.
                </p>
            </div>
        </div>

        {{-- Sub-navigation Tabs --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-slate-200">
            <a href="{{ route('admin.personnel.index') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.index') ? 'bg-[#173957] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <i data-lucide="users" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Directory
            </a>
            <a href="{{ route('admin.personnel.teams') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.teams') ? 'bg-[#173957] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <i data-lucide="shield" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Response Teams
            </a>
            <a href="{{ route('admin.personnel.jurisdictions') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.jurisdictions') ? 'bg-[#173957] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <i data-lucide="map-pin" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Jurisdictions
            </a>
            <a href="{{ route('admin.personnel.availability') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.availability') ? 'bg-[#173957] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <i data-lucide="activity" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Availability Monitor
            </a>
            <a href="{{ route('admin.personnel.assignments') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.assignments') ? 'bg-[#173957] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <i data-lucide="radio" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Active Assignments ({{ $assignments->total() }})
            </a>
            <a href="{{ route('admin.personnel.bases') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.bases') ? 'bg-[#173957] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <i data-lucide="home" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Response Bases
            </a>
        </div>

        {{-- Active Assignments Table --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-[10px] tracking-wider font-bold">
                        <tr>
                            <th class="py-3 px-4">Incident Reference</th>
                            <th class="py-3 px-4">Emergency Category</th>
                            <th class="py-3 px-4">Location / Barangay</th>
                            <th class="py-3 px-4">Dispatched Unit</th>
                            <th class="py-3 px-4">Assigned At</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($assignments as $assignment)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                    {{ $assignment->incident?->reference_number ?? '#INC-' . $assignment->incident_id }}
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold bg-rose-50 text-rose-800 border border-rose-200">
                                        {{ $assignment->incident?->category?->name ?? 'Emergency' }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-slate-700">
                                    <div class="font-bold">{{ $assignment->incident?->barangay?->name ?? 'Hinoba-an' }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $assignment->incident?->location?->street_address ?? $assignment->incident?->location?->street_name ?? 'Scene coordinates' }}</div>
                                </td>

                                <td class="py-3.5 px-4">
                                    @if($assignment->personnel)
                                        <div class="font-bold text-slate-900">{{ $assignment->personnel->user?->name }}</div>
                                        <div class="text-[10px] text-slate-500 font-mono">Badge #{{ $assignment->personnel->badge_number }}</div>
                                    @elseif($assignment->team)
                                        <div class="font-bold text-slate-900">{{ $assignment->team->team_name }}</div>
                                        <div class="text-[10px] text-blue-600 font-mono">Call Sign: {{ $assignment->team->call_sign }}</div>
                                    @else
                                        <div class="text-slate-400 italic">Office Direct</div>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-slate-600 font-mono text-[11px]">
                                    <div>{{ $assignment->assigned_at ? $assignment->assigned_at->format('M d, H:i') : '—' }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $assignment->assigned_at ? $assignment->assigned_at->diffForHumans() : '' }}</div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span> Active Deployment
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    @if($assignment->incident?->reference_number)
                                        <a href="{{ route('admin.incidents.show', $assignment->incident->reference_number) }}" 
                                           class="px-3 py-1.5 rounded-lg text-xs font-bold text-[#173957] hover:bg-slate-100 transition-colors">
                                            Review Scene
                                        </a>
                                    @else
                                        <span class="px-3 py-1.5 text-xs text-slate-400">Unavailable</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <i data-lucide="radio" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                    <p class="font-bold text-slate-600">No active emergency assignments.</p>
                                    <p class="text-xs text-slate-400 mt-1">All dispatched units have resolved their tasks.</p>
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
</x-layouts.admin>
