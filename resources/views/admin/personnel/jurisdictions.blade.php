<x-layouts.admin :title="'Personnel Jurisdictions — ALERTO EOC'">
    <div class="space-y-6">

        {{-- Top Bar & Title --}}
        <x-portal.page-header
            badge="Geographic Operations · Hinoba-an"
            title="Personnel &amp; Team Jurisdictions"
            subtitle="Coverage allocation across the 13 Barangays of the Municipality of Hinoba-an."
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
                Jurisdictions (13 Barangays)
            </a>
            <a href="{{ route('admin.personnel.availability') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.availability') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                <i data-lucide="activity" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Availability Monitor
            </a>
            <a href="{{ route('admin.personnel.assignments') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.assignments') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                <i data-lucide="radio" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Active Assignments
            </a>
            <a href="{{ route('admin.personnel.bases') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.bases') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                <i data-lucide="home" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Response Bases
            </a>
        </div>

        {{-- Hinoba-an 13 Barangays Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($barangays as $bg)
                @php
                    $personnelCount = $bg->personnelJurisdictions->count();
                    $teamCount = $bg->teamJurisdictions->count();
                @endphp
                <div class="alerto-interactive-card alerto-card p-5">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <span class="text-[10px] font-mono font-bold text-[#77736E] uppercase">BARANGAY</span>
                            <h3 class="text-base font-black">{{ $bg->name }}</h3>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $personnelCount > 0 ? 'bg-[#27845B]/15 text-[#27845B]' : 'bg-[#D99024]/15 text-[#B94F12] dark:text-[#F29A56]' }}">
                            {{ $personnelCount > 0 ? 'Covered' : 'Low Coverage' }}
                        </span>
                    </div>

                    <div class="space-y-3 text-xs border-t border-[#E6E3DE] dark:border-white/10 pt-3">
                        <div>
                            <div class="flex items-center justify-between text-[#77736E] mb-1">
                                <span class="font-bold">Assigned Operatives:</span>
                                <span class="font-black text-[#B94F12] dark:text-[#F29A56]">{{ $personnelCount }}</span>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                @forelse($bg->personnelJurisdictions->take(3) as $pj)
                                    <span class="px-2 py-0.5 rounded-md text-[10px] bg-[#F5F3EF] dark:bg-white/10 font-medium">
                                        {{ $pj->personnel?->user?->name ?? 'Operative' }}
                                    </span>
                                @empty
                                    <span class="text-[11px] text-[#77736E] italic">No assigned personnel</span>
                                @endforelse
                                @if($personnelCount > 3)
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-[#F5F3EF] dark:bg-white/10 text-[#77736E]">
                                        +{{ $personnelCount - 3 }} more
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between text-[#77736E] mb-1">
                                <span class="font-bold">Assigned Response Teams:</span>
                                <span class="font-black text-[#E87524]">{{ $teamCount }}</span>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                @forelse($bg->teamJurisdictions as $tj)
                                    <span class="px-2 py-0.5 rounded-md text-[10px] bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56] font-bold border border-[#E87524]/25">
                                        {{ $tj->team?->call_sign ?? 'TEAM' }}
                                    </span>
                                @empty
                                    <span class="text-[11px] text-[#77736E] italic">No direct team assignment</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</x-layouts.admin>
