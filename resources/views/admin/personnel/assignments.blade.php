<x-layouts.admin :title="'Active Assignments — ALERTO EOC'">
    <div class="space-y-6">

        {{-- Top Bar & Title --}}
        <x-portal.page-header
            badge="Operational Missions · Hinoba-an"
            title="Active Personnel Assignments"
            subtitle="Live tracking of field deployments, tactical response units on scene, and elapsed mission durations."
        />

        {{-- Sub-navigation Tabs --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-[#E6E3DE] dark:border-white/10">
            <a href="{{ route('admin.personnel.index') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.index') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                <i data-lucide="users" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Directory
            </a>
            <a href="{{ route('admin.personnel.teams') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.teams') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                <i data-lucide="shield" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Response Teams
            </a>
            <a href="{{ route('admin.personnel.jurisdictions') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.jurisdictions') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                <i data-lucide="map-pin" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Jurisdictions
            </a>
            <a href="{{ route('admin.personnel.availability') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.availability') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                <i data-lucide="activity" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Availability Monitor
            </a>
            <a href="{{ route('admin.personnel.assignments') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.assignments') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                <i data-lucide="radio" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Active Assignments ({{ $assignments->total() }})
            </a>
            <a href="{{ route('admin.personnel.bases') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.bases') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                <i data-lucide="home" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Response Bases
            </a>
        </div>

        {{-- Active Assignments Table --}}
        <div class="alerto-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F5F3EF] dark:bg-white/5 border-b border-[#E6E3DE] dark:border-white/10 text-[#77736E] uppercase text-[10px] tracking-wider font-bold">
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
                    <tbody class="divide-y divide-[#E6E3DE] dark:divide-white/10">
                        @forelse($assignments as $assignment)
                            <tr class="hover:bg-[#FFF0E4]/40 dark:hover:bg-white/5 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold text-[#B94F12] dark:text-[#F29A56]">
                                    {{ $assignment->incident?->reference_number ?? '#INC-' . $assignment->incident_id }}
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56] border border-[#E87524]/25">
                                        {{ $assignment->incident?->category?->name ?? 'Emergency' }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="font-bold">{{ $assignment->incident?->barangay?->name ?? 'Hinoba-an' }}</div>
                                    <div class="text-[11px] text-[#77736E]">{{ $assignment->incident?->location?->street_address ?? $assignment->incident?->location?->street_name ?? 'Scene coordinates' }}</div>
                                </td>

                                <td class="py-3.5 px-4">
                                    @if($assignment->personnel)
                                        <div class="font-bold">{{ $assignment->personnel->user?->name }}</div>
                                        <div class="text-[10px] text-[#77736E] font-mono">Badge #{{ $assignment->personnel->badge_number }}</div>
                                    @elseif($assignment->team)
                                        <div class="font-bold">{{ $assignment->team->team_name }}</div>
                                        <div class="text-[10px] text-[#E87524] font-mono">Call Sign: {{ $assignment->team->call_sign }}</div>
                                    @else
                                        <div class="text-[#77736E] italic">Office Direct</div>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-[#77736E] font-mono text-[11px]">
                                    <div>{{ $assignment->assigned_at ? $assignment->assigned_at->format('M d, H:i') : '—' }}</div>
                                    <div class="text-[10px]">{{ $assignment->assigned_at ? $assignment->assigned_at->diffForHumans() : '' }}</div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#27845B]/15 text-[#27845B]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#27845B]"></span> Active Deployment
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    @if($assignment->incident?->reference_number)
                                        <a href="{{ route('admin.incidents.show', $assignment->incident->reference_number) }}"
                                           class="px-3 py-1.5 rounded-lg text-xs font-bold text-[#B94F12] dark:text-[#F29A56] hover:bg-[#E87524]/15 transition-colors">
                                            Review Scene
                                        </a>
                                    @else
                                        <span class="px-3 py-1.5 text-xs text-[#77736E]">Unavailable</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-[#77736E]">
                                    <i data-lucide="radio" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                    <p class="font-bold">No active emergency assignments.</p>
                                    <p class="text-xs mt-1">All dispatched units have resolved their tasks.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($assignments->hasPages())
                <div class="p-4 border-t border-[#E6E3DE] dark:border-white/10 bg-[#F5F3EF]/60 dark:bg-white/5">
                    {{ $assignments->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layouts.admin>
