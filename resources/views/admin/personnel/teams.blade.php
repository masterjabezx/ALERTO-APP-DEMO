<x-layouts.admin>
    <div class="space-y-6" x-data="{ createModalOpen: false, editModalOpen: false, editingTeam: null }">

        {{-- Top Bar & Title --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#173957]/10 text-[#173957] text-[11px] font-bold uppercase tracking-wider mb-1">
                    <i data-lucide="shield" class="w-3.5 h-3.5 text-[#3187D8]"></i>
                    Tactical Response Units · Hinoba-an
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                    Emergency Response Teams
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Manage multi-disciplinary emergency squads, radio call signs, team leaders, and operational jurisdiction coverage.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" 
                        @click="createModalOpen = true" 
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#173957] hover:bg-[#1f4b72] text-white font-bold text-xs shadow-md transition-all">
                    <i data-lucide="plus" class="w-4 h-4 text-[#3187D8]"></i>
                    <span>Create Response Team</span>
                </button>
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
                Response Teams ({{ $teams->count() }})
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
                Active Assignments
            </a>
            <a href="{{ route('admin.personnel.bases') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.bases') ? 'bg-[#173957] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <i data-lucide="home" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Response Bases
            </a>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center gap-3">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- Teams Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($teams as $team)
                <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs flex flex-col justify-between hover:border-[#3187D8]/50 transition-all">
                    <div>
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $team->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $team->team_type ?? 'Emergency Team' }}
                                </span>
                                <h3 class="text-base font-black text-slate-900 mt-1">{{ $team->team_name }}</h3>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-[#173957] text-white shadow-xs">
                                {{ $team->call_sign }}
                            </span>
                        </div>

                        <p class="text-xs text-slate-500 mb-4 line-clamp-2">
                            {{ $team->description ?? 'Specialized emergency response squad attached to ' . ($team->office?->name ?? 'MDRRMO') . '.' }}
                        </p>

                        <div class="space-y-2 text-xs border-t border-slate-100 pt-3">
                            <div class="flex items-center justify-between text-slate-600">
                                <span class="text-slate-400">Office:</span>
                                <span class="font-bold text-slate-800">{{ $team->office?->name ?? 'N/A' }}</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600">
                                <span class="text-slate-400">Base Station:</span>
                                <span class="font-bold text-slate-800">{{ $team->base?->base_name ?? 'Central HQ' }}</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600">
                                <span class="text-slate-400">Team Leader:</span>
                                <span class="font-bold text-slate-800">{{ $team->leader?->user?->name ?? 'Unassigned' }}</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600">
                                <span class="text-slate-400">Active Operatives:</span>
                                <span class="font-bold text-[#173957]">{{ $team->members->count() }} member(s)</span>
                            </div>
                        </div>

                        {{-- Jurisdictions tags --}}
                        <div class="mt-3 pt-3 border-t border-slate-100">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1.5">Coverage ({{ $team->barangays->count() }} Barangays):</span>
                            <div class="flex flex-wrap gap-1">
                                @forelse($team->barangays->take(4) as $bg)
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-medium bg-slate-100 text-slate-600">
                                        {{ $bg->name }}
                                    </span>
                                @empty
                                    <span class="text-[10px] text-slate-400 italic">Hinoba-an General</span>
                                @endforelse
                                @if($team->barangays->count() > 4)
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-500">
                                        +{{ $team->barangays->count() - 4 }} more
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-[11px] text-slate-400">ID: #{{ $team->id }}</span>
                        <button type="button" 
                                @click="editingTeam = {{ json_encode($team) }}; editModalOpen = true"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold text-[#173957] hover:bg-slate-100 transition-colors">
                            Manage Team
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center text-slate-400 bg-white rounded-2xl border border-slate-200">
                    <i data-lucide="shield-off" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                    <p class="font-bold text-slate-600">No response teams configured.</p>
                    <p class="text-xs text-slate-400 mt-1">Click "Create Response Team" above to set up tactical units.</p>
                </div>
            @endforelse
        </div>

        {{-- Create Team Modal --}}
        <div x-show="createModalOpen" x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
             @keydown.escape.window="createModalOpen = false">
            <div class="w-full max-w-xl bg-white rounded-2xl shadow-xl p-6 border border-slate-200 max-h-[90vh] overflow-y-auto" @click.away="createModalOpen = false">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <h3 class="text-base font-bold text-slate-900">Create Response Team</h3>
                    <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.personnel.teams.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Team Name <span class="text-rose-500">*</span></label>
                            <input type="text" name="team_name" required placeholder="e.g., Alpha Rescue Unit" 
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Call Sign <span class="text-rose-500">*</span></label>
                            <input type="text" name="call_sign" required placeholder="e.g., ALPHA-1" 
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Parent Office <span class="text-rose-500">*</span></label>
                            <select name="office_id" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                                @foreach($offices as $office)
                                    <option value="{{ $office->id }}">{{ $office->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Response Base Station</label>
                            <select name="response_base_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                                <option value="">Select Physical Base</option>
                                @foreach($bases as $base)
                                    <option value="{{ $base->id }}">{{ $base->base_name }} ({{ $base->barangay?->name }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Team Type</label>
                            <select name="team_type" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                                <option value="Medical">Medical / Paramedic</option>
                                <option value="Rescue">Search &amp; Rescue</option>
                                <option value="Fire">Fire Suppression</option>
                                <option value="Police">Law Enforcement / Traffic</option>
                                <option value="Logistics">Logistics / Relief</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Team Leader</label>
                            <select name="team_leader_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                                <option value="">Select Team Leader</option>
                                @foreach($allPersonnel as $person)
                                    <option value="{{ $person->id }}">{{ $person->user?->name }} (#{{ $person->badge_number }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Description / Operational Scope</label>
                        <textarea name="description" rows="2" placeholder="Brief mission statement or operational scope" 
                                  class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Coverage Barangays</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-36 overflow-y-auto p-2 border border-slate-100 rounded-xl bg-slate-50">
                            @foreach($barangays as $bg)
                                <label class="flex items-center gap-2 text-xs">
                                    <input type="checkbox" name="barangay_ids[]" value="{{ $bg->id }}" class="rounded text-[#173957]">
                                    <span class="truncate">{{ $bg->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="createModalOpen = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold bg-[#173957] text-white hover:bg-[#1f4b72] rounded-xl shadow-xs">
                            Save Response Team
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Manage/Edit Team Modal --}}
        <div x-show="editModalOpen" x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
             @keydown.escape.window="editModalOpen = false">
            <div class="w-full max-w-xl bg-white rounded-2xl shadow-xl p-6 border border-slate-200" @click.away="editModalOpen = false">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <h3 class="text-base font-bold text-slate-900">Manage Team: <span x-text="editingTeam?.team_name"></span></h3>
                    <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form :action="`{{ url('admin/personnel/teams') }}/${editingTeam?.id}`" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Team Name</label>
                            <input type="text" name="team_name" :value="editingTeam?.team_name" required 
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Call Sign</label>
                            <input type="text" name="call_sign" :value="editingTeam?.call_sign" required 
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Status</label>
                        <select name="is_active" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
                            <option value="1" :selected="editingTeam?.is_active">Active</option>
                            <option value="0" :selected="!editingTeam?.is_active">Inactive / Standby</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="editModalOpen = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold bg-[#173957] text-white hover:bg-[#1f4b72] rounded-xl shadow-xs">
                            Update Team
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-layouts.admin>
