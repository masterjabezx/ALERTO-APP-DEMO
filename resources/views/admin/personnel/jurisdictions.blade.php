<x-layouts.admin>
    <div class="space-y-6">

        {{-- Top Bar & Title --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#173957]/10 text-[#173957] text-[11px] font-bold uppercase tracking-wider mb-1">
                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-[#3187D8]"></i>
                    Geographic Operations · Hinoba-an
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                    Personnel &amp; Team Jurisdictions
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Coverage allocation across the 13 Barangays of the Municipality of Hinoba-an.
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
                Jurisdictions (13 Barangays)
            </a>
            <a href="{{ route('admin.personnel.availability') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.availability') ? 'bg-[#173957] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <i data-lucide="activity" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Availability Monitor
            </a>
            <a href="{{ route('admin.personnel.assignments') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.assignments') ? 'bg-[#173957] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <i data-lucide="radio" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Active Assignments
            </a>
            <a href="{{ route('admin.personnel.bases') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.bases') ? 'bg-[#173957] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
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
                <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs hover:border-[#3187D8]/50 transition-all">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <span class="text-[10px] font-mono font-bold text-slate-400 uppercase">BARANGAY</span>
                            <h3 class="text-base font-black text-slate-900">{{ $bg->name }}</h3>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $personnelCount > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ $personnelCount > 0 ? 'Covered' : 'Low Coverage' }}
                        </span>
                    </div>

                    <div class="space-y-3 text-xs border-t border-slate-100 pt-3">
                        <div>
                            <div class="flex items-center justify-between text-slate-500 mb-1">
                                <span class="font-bold">Assigned Operatives:</span>
                                <span class="font-black text-[#173957]">{{ $personnelCount }}</span>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                @forelse($bg->personnelJurisdictions->take(3) as $pj)
                                    <span class="px-2 py-0.5 rounded-md text-[10px] bg-slate-100 text-slate-700 font-medium">
                                        {{ $pj->personnel?->user?->name ?? 'Operative' }}
                                    </span>
                                @empty
                                    <span class="text-[11px] text-slate-400 italic">No assigned personnel</span>
                                @endforelse
                                @if($personnelCount > 3)
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-500">
                                        +{{ $personnelCount - 3 }} more
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between text-slate-500 mb-1">
                                <span class="font-bold">Assigned Response Teams:</span>
                                <span class="font-black text-[#3187D8]">{{ $teamCount }}</span>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                @forelse($bg->teamJurisdictions as $tj)
                                    <span class="px-2 py-0.5 rounded-md text-[10px] bg-blue-50 text-blue-700 font-bold border border-blue-200">
                                        {{ $tj->team?->call_sign ?? 'TEAM' }}
                                    </span>
                                @empty
                                    <span class="text-[11px] text-slate-400 italic">No direct team assignment</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</x-layouts.admin>
