<x-layouts.admin :title="'Response Teams — ALERTO EOC'">
    <div class="space-y-6" x-data="{ createModalOpen: false, editModalOpen: false, editingTeam: null }">

        {{-- Top Bar & Title --}}
        <x-portal.page-header
            badge="Tactical Response Units · Hinoba-an"
            title="Emergency Response Teams"
            subtitle="Manage multi-disciplinary emergency squads, radio call signs, team leaders, and operational jurisdiction coverage."
        >
            <x-slot:actions>
                <button type="button"
                        @click="createModalOpen = true"
                        class="alerto-btn-press inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white font-bold text-xs shadow-md transition-all">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Create Response Team</span>
                </button>
            </x-slot:actions>
        </x-portal.page-header>

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
                Response Teams ({{ $teams->count() }})
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
                Active Assignments
            </a>
            <a href="{{ route('admin.personnel.bases') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.bases') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                <i data-lucide="home" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Response Bases
            </a>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-xl bg-[#27845B]/15 border border-[#27845B]/30 text-[#27845B] text-xs font-medium flex items-center gap-3">
                <i data-lucide="check-circle" class="w-4 h-4 text-[#27845B] shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- Teams Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($teams as $team)
                <div class="alerto-interactive-card alerto-card p-5 flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $team->is_active ? 'bg-[#27845B]/15 text-[#27845B]' : 'bg-black/5 dark:bg-white/10 text-[#77736E]' }}">
                                    {{ $team->team_type ?? 'Emergency Team' }}
                                </span>
                                <h3 class="text-base font-black mt-1">{{ $team->team_name }}</h3>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-[#17191C] text-[#F29A56] border border-[#E87524]/40 shadow-xs">
                                {{ $team->call_sign }}
                            </span>
                        </div>

                        <p class="text-xs text-[#77736E] mb-4 line-clamp-2">
                            {{ $team->description ?? 'Specialized emergency response squad attached to ' . ($team->office?->name ?? 'MDRRMO') . '.' }}
                        </p>

                        <div class="space-y-2 text-xs border-t border-[#E6E3DE] dark:border-white/10 pt-3">
                            <div class="flex items-center justify-between">
                                <span class="text-[#77736E]">Office:</span>
                                <span class="font-bold">{{ $team->office?->name ?? 'N/A' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-[#77736E]">Base Station:</span>
                                <span class="font-bold">{{ $team->base?->base_name ?? 'Central HQ' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-[#77736E]">Team Leader:</span>
                                <span class="font-bold">{{ $team->leader?->user?->name ?? 'Unassigned' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-[#77736E]">Active Operatives:</span>
                                <span class="font-bold text-[#B94F12] dark:text-[#F29A56]">{{ $team->members->count() }} member(s)</span>
                            </div>
                        </div>

                        {{-- Jurisdictions tags --}}
                        <div class="mt-3 pt-3 border-t border-[#E6E3DE] dark:border-white/10">
                            <span class="text-[10px] uppercase font-bold text-[#77736E] block mb-1.5">Coverage ({{ $team->barangays->count() }} Barangays):</span>
                            <div class="flex flex-wrap gap-1">
                                @forelse($team->barangays->take(4) as $bg)
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-medium bg-[#F5F3EF] dark:bg-white/10">
                                        {{ $bg->name }}
                                    </span>
                                @empty
                                    <span class="text-[10px] text-[#77736E] italic">Hinoba-an General</span>
                                @endforelse
                                @if($team->barangays->count() > 4)
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-[#F5F3EF] dark:bg-white/10 text-[#77736E]">
                                        +{{ $team->barangays->count() - 4 }} more
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-[#E6E3DE] dark:border-white/10 flex items-center justify-between">
                        <span class="text-[11px] text-[#77736E]">ID: #{{ $team->id }}</span>
                        <button type="button"
                                @click="editingTeam = {{ json_encode($team) }}; editModalOpen = true"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold text-[#B94F12] dark:text-[#F29A56] hover:bg-[#E87524]/15 transition-colors">
                            Manage Team
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center text-[#77736E] alerto-card">
                    <i data-lucide="shield-off" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                    <p class="font-bold">No response teams configured.</p>
                    <p class="text-xs mt-1">Click "Create Response Team" above to set up tactical units.</p>
                </div>
            @endforelse
        </div>

        {{-- Create Team Modal --}}
        <div x-show="createModalOpen" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/65 backdrop-blur-xs"
             @keydown.escape.window="createModalOpen = false">
            <div class="w-full max-w-xl alerto-card shadow-xl p-6 max-h-[90vh] overflow-y-auto" @click.away="createModalOpen = false">
                <div class="flex items-center justify-between pb-3 border-b border-[#E6E3DE] dark:border-white/10 mb-4">
                    <h3 class="text-base font-bold">Create Response Team</h3>
                    <button type="button" @click="createModalOpen = false" class="text-[#77736E] hover:text-[#17191C] dark:hover:text-white">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.personnel.teams.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold mb-1">Team Name <span class="text-[#C83F43]">*</span></label>
                            <input type="text" name="team_name" required placeholder="e.g., Alpha Rescue Unit"
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold mb-1">Call Sign <span class="text-[#C83F43]">*</span></label>
                            <input type="text" name="call_sign" required placeholder="e.g., ALPHA-1"
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold mb-1">Parent Office <span class="text-[#C83F43]">*</span></label>
                            <select name="office_id" required class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                                @foreach($offices as $office)
                                    <option value="{{ $office->id }}">{{ $office->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold mb-1">Response Base Station</label>
                            <select name="response_base_id" class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                                <option value="">Select Physical Base</option>
                                @foreach($bases as $base)
                                    <option value="{{ $base->id }}">{{ $base->base_name }} ({{ $base->barangay?->name }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold mb-1">Team Type</label>
                            <select name="team_type" class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                                <option value="Medical">Medical / Paramedic</option>
                                <option value="Rescue">Search &amp; Rescue</option>
                                <option value="Fire">Fire Suppression</option>
                                <option value="Police">Law Enforcement / Traffic</option>
                                <option value="Logistics">Logistics / Relief</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold mb-1">Team Leader</label>
                            <select name="team_leader_id" class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                                <option value="">Select Team Leader</option>
                                @foreach($allPersonnel as $person)
                                    <option value="{{ $person->id }}">{{ $person->user?->name }} (#{{ $person->badge_number }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">Description / Operational Scope</label>
                        <textarea name="description" rows="2" placeholder="Brief mission statement or operational scope"
                                  class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">Coverage Barangays</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-36 overflow-y-auto p-2 border border-[#E6E3DE] dark:border-white/15 rounded-xl bg-[#F5F3EF] dark:bg-white/5">
                            @foreach($barangays as $bg)
                                <label class="flex items-center gap-2 text-xs">
                                    <input type="checkbox" name="barangay_ids[]" value="{{ $bg->id }}" class="rounded text-[#E87524] focus:ring-[#E87524]">
                                    <span class="truncate">{{ $bg->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#E6E3DE] dark:border-white/10">
                        <button type="button" @click="createModalOpen = false" class="px-4 py-2 text-xs font-bold text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10 rounded-xl">
                            Cancel
                        </button>
                        <button type="submit" class="alerto-btn-press px-5 py-2 text-xs font-bold bg-[#E87524] text-white hover:bg-[#B94F12] rounded-xl shadow-xs">
                            Save Response Team
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Manage/Edit Team Modal --}}
        <div x-show="editModalOpen" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/65 backdrop-blur-xs"
             @keydown.escape.window="editModalOpen = false">
            <div class="w-full max-w-xl alerto-card shadow-xl p-6" @click.away="editModalOpen = false">
                <div class="flex items-center justify-between pb-3 border-b border-[#E6E3DE] dark:border-white/10 mb-4">
                    <h3 class="text-base font-bold">Manage Team: <span x-text="editingTeam?.team_name"></span></h3>
                    <button type="button" @click="editModalOpen = false" class="text-[#77736E] hover:text-[#17191C] dark:hover:text-white">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form :action="`{{ url('admin/personnel/teams') }}/${editingTeam?.id}`" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold mb-1">Team Name</label>
                            <input type="text" name="team_name" :value="editingTeam?.team_name" required
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold mb-1">Call Sign</label>
                            <input type="text" name="call_sign" :value="editingTeam?.call_sign" required
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">Status</label>
                        <select name="is_active" class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                            <option value="1" :selected="editingTeam?.is_active">Active</option>
                            <option value="0" :selected="!editingTeam?.is_active">Inactive / Standby</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#E6E3DE] dark:border-white/10">
                        <button type="button" @click="editModalOpen = false" class="px-4 py-2 text-xs font-bold text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10 rounded-xl">
                            Cancel
                        </button>
                        <button type="submit" class="alerto-btn-press px-5 py-2 text-xs font-bold bg-[#E87524] text-white hover:bg-[#B94F12] rounded-xl shadow-xs">
                            Update Team
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-layouts.admin>
