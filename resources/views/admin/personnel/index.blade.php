<x-layouts.admin :title="'Personnel Management — ALERTO EOC'">
    <div class="space-y-6">

        {{-- Top Bar & Title --}}
        <x-portal.page-header
            badge="Emergency Operations Center · Hinoba-an"
            title="Personnel Management &amp; Response Units"
            subtitle="Field operatives directory, emergency teams, operational availability, jurisdictions, and response bases."
        >
            <x-slot:actions>
                <a href="{{ route('admin.personnel.create') }}"
                   class="alerto-btn-press inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white font-bold text-xs shadow-md transition-all">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    <span>Register Personnel</span>
                </a>
            </x-slot:actions>
        </x-portal.page-header>

        {{-- Sub-navigation Tabs --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-[#E6E3DE] dark:border-white/10">
            <a href="{{ route('admin.personnel.index') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.index') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                <i data-lucide="users" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Directory ({{ $stats['total'] }})
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
                Active Assignments ({{ $stats['active_assignments'] }})
            </a>
            <a href="{{ route('admin.personnel.bases') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.bases') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                <i data-lucide="home" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Response Bases
            </a>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="p-4 rounded-xl bg-[#27845B]/15 border border-[#27845B]/30 text-[#27845B] text-xs font-medium flex items-center gap-3">
                <i data-lucide="check-circle" class="w-4 h-4 text-[#27845B] shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 rounded-xl bg-[#C83F43]/15 border border-[#C83F43]/30 text-[#C83F43] text-xs font-medium flex items-center gap-3">
                <i data-lucide="alert-octagon" class="w-4 h-4 text-[#C83F43] shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- KPI Quick Metrics Bar (Real Database Counts) --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
            <div class="alerto-kpi-card alerto-card p-4">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#77736E]">Total Personnel</span>
                    <i data-lucide="users" class="w-4 h-4 text-[#77736E]"></i>
                </div>
                <p class="text-2xl font-black font-mono">{{ number_format($stats['total']) }}</p>
                <span class="text-[10px] text-[#77736E]">Registered staff</span>
            </div>

            <div class="alerto-kpi-card alerto-card p-4 border-[#27845B]/30">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#27845B]">Available</span>
                    <i data-lucide="check-circle" class="w-4 h-4 text-[#27845B]"></i>
                </div>
                <p class="text-2xl font-black font-mono text-[#27845B]">{{ number_format($stats['available']) }}</p>
                <span class="text-[10px] text-[#77736E]">Ready for dispatch</span>
            </div>

            <div class="alerto-kpi-card alerto-card p-4 border-[#397BA8]/30">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#397BA8]">Dispatched</span>
                    <i data-lucide="send" class="w-4 h-4 text-[#397BA8]"></i>
                </div>
                <p class="text-2xl font-black font-mono text-[#397BA8]">{{ number_format($stats['dispatched']) }}</p>
                <span class="text-[10px] text-[#77736E]">En route to scene</span>
            </div>

            <div class="alerto-kpi-card alerto-card p-4 border-[#E87524]/30">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#B94F12] dark:text-[#F29A56]">On Scene</span>
                    <i data-lucide="map-pin" class="w-4 h-4 text-[#E87524]"></i>
                </div>
                <p class="text-2xl font-black font-mono text-[#E87524]">{{ number_format($stats['on_scene']) }}</p>
                <span class="text-[10px] text-[#77736E]">Conducting operations</span>
            </div>

            <div class="alerto-kpi-card alerto-card p-4">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#77736E]">Off Duty</span>
                    <i data-lucide="moon" class="w-4 h-4 text-[#77736E]"></i>
                </div>
                <p class="text-2xl font-black font-mono">{{ number_format($stats['off_duty']) }}</p>
                <span class="text-[10px] text-[#77736E]">Standby / off-shift</span>
            </div>

            <div class="alerto-kpi-card alerto-card p-4 border-[#D99024]/30">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#D99024]">Active Missions</span>
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-[#D99024]"></i>
                </div>
                <p class="text-2xl font-black font-mono text-[#D99024]">{{ number_format($stats['active_assignments']) }}</p>
                <span class="text-[10px] text-[#77736E]">Live emergency runs</span>
            </div>
        </div>

        {{-- Filters Bar --}}
        <div class="alerto-card p-4">
            <form method="GET" action="{{ route('admin.personnel.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-[#77736E] mb-1">Search</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="Badge, name, email, phone..."
                           class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-[#77736E] mb-1">Office</label>
                    <select name="office_id" class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                        <option value="">All Offices</option>
                        @foreach($offices as $office)
                            <option value="{{ $office->id }}" {{ ($filters['office_id'] ?? '') == $office->id ? 'selected' : '' }}>
                                {{ $office->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-[#77736E] mb-1">Response Team</label>
                    <select name="team_id" class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                        <option value="">All Teams</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}" {{ ($filters['team_id'] ?? '') == $team->id ? 'selected' : '' }}>
                                {{ $team->team_name }} ({{ $team->call_sign }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-[#77736E] mb-1">Operational Status</label>
                    <select name="operational_status" class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                        <option value="">All Statuses</option>
                        <option value="available" {{ ($filters['operational_status'] ?? '') == 'available' ? 'selected' : '' }}>Available (Ready)</option>
                        <option value="dispatched" {{ ($filters['operational_status'] ?? '') == 'dispatched' ? 'selected' : '' }}>Dispatched (En Route)</option>
                        <option value="on_scene" {{ ($filters['operational_status'] ?? '') == 'on_scene' ? 'selected' : '' }}>On Scene</option>
                        <option value="busy" {{ ($filters['operational_status'] ?? '') == 'busy' ? 'selected' : '' }}>Busy</option>
                        <option value="off_duty" {{ ($filters['operational_status'] ?? '') == 'off_duty' ? 'selected' : '' }}>Off Duty</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="alerto-btn-press flex-1 py-2 px-3 bg-[#E87524] hover:bg-[#B94F12] text-white font-bold text-xs rounded-xl transition-colors">
                        Apply Filters
                    </button>
                    @if(!empty(array_filter($filters)))
                        <a href="{{ route('admin.personnel.index') }}" class="py-2 px-3 bg-[#F5F3EF] dark:bg-white/10 text-[#454A50] dark:text-[#CFCBC4] font-bold text-xs rounded-xl hover:bg-[#E6E3DE] dark:hover:bg-white/20 transition-colors">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Personnel Directory Table --}}
        <div class="alerto-card overflow-hidden" x-data="{ resetModalOpen: false, activeProfileId: null, activeBadge: '' }">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F5F3EF] dark:bg-white/5 border-b border-[#E6E3DE] dark:border-white/10 text-[#77736E] uppercase text-[10px] tracking-wider font-bold">
                        <tr>
                            <th class="py-3 px-4">Operative &amp; Badge</th>
                            <th class="py-3 px-4">Contact</th>
                            <th class="py-3 px-4">Office &amp; Rank</th>
                            <th class="py-3 px-4">Team &amp; Jurisdictions</th>
                            <th class="py-3 px-4">Duty Status</th>
                            <th class="py-3 px-4">Presence</th>
                            <th class="py-3 px-4 text-center">Account</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E6E3DE] dark:divide-white/10">
                        @forelse($personnel as $p)
                            @php
                                $opStatus = $p->availability?->operational_status ?? 'off_duty';
                                $presenceStatus = $p->user?->userPresence?->status ?? 'offline';
                                $isAccountActive = (bool) $p->user?->is_active;
                            @endphp
                            <tr class="hover:bg-[#FFF0E4]/40 dark:hover:bg-white/5 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56] font-black text-xs flex items-center justify-center shrink-0">
                                            {{ substr($p->user?->name ?? 'P', 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="font-bold flex items-center gap-1.5">
                                                <span>{{ $p->user?->name ?? 'Unknown' }}</span>
                                                @if($p->is_team_leader)
                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56]">LEADER</span>
                                                @endif
                                            </div>
                                            <div class="text-[11px] font-mono text-[#77736E]">
                                                #{{ $p->badge_number }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 text-[#454A50] dark:text-[#CFCBC4]">
                                    <div>{{ $p->user?->email }}</div>
                                    <div class="text-[11px] text-[#77736E] font-mono">{{ $p->user?->phone_number ?? 'No phone' }}</div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="font-bold">{{ $p->office?->name ?? 'Unassigned' }}</div>
                                    <div class="text-[11px] text-[#77736E]">{{ $p->rank_designation }}</div>
                                    @if($p->specialization)
                                        <div class="text-[10px] text-[#B94F12] dark:text-[#F29A56] font-medium">{{ $p->specialization }}</div>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="flex flex-wrap gap-1 mb-1">
                                        @forelse($p->teams as $team)
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56] border border-[#E87524]/25">
                                                {{ $team->call_sign }}
                                            </span>
                                        @empty
                                            <span class="text-[11px] text-[#77736E] italic">No team</span>
                                        @endforelse
                                    </div>
                                    <div class="text-[10px] text-[#77736E]">
                                        {{ $p->jurisdictions->count() }} barangay(s)
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    @if($opStatus === 'available')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#27845B]/15 text-[#27845B]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#27845B]"></span> Available
                                        </span>
                                    @elseif($opStatus === 'dispatched')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#397BA8]/15 text-[#397BA8]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#397BA8]"></span> Dispatched
                                        </span>
                                    @elseif($opStatus === 'on_scene')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#E87524]"></span> On Scene
                                        </span>
                                    @elseif($opStatus === 'busy')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#D99024]/15 text-[#D99024]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#D99024]"></span> Busy
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-black/5 dark:bg-white/10 text-[#77736E]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#77736E]"></span> Off Duty
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4">
                                    @if($presenceStatus === 'online')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-[#27845B]/15 text-[#27845B]">
                                            <span class="w-2 h-2 rounded-full bg-[#27845B] animate-pulse"></span> Online
                                        </span>
                                    @elseif($presenceStatus === 'connection_lost')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-[#C83F43]/15 text-[#C83F43]">
                                            <span class="w-2 h-2 rounded-full bg-[#C83F43]"></span> Lost
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-black/5 dark:bg-white/10 text-[#77736E]">
                                            <span class="w-2 h-2 rounded-full bg-[#77736E]"></span> Offline
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <form method="POST" action="{{ route('admin.personnel.toggle-status', $p->id) }}" onsubmit="return confirm('Toggle active status for this personnel?');">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded-full text-[10px] font-bold transition-all {{ $isAccountActive ? 'bg-[#27845B]/15 hover:bg-[#27845B]/25 text-[#27845B]' : 'bg-[#C83F43]/15 hover:bg-[#C83F43]/25 text-[#C83F43]' }}">
                                            {{ $isAccountActive ? 'Active' : 'Disabled' }}
                                        </button>
                                    </form>
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="{{ route('admin.personnel.edit', $p->id) }}"
                                           class="p-1.5 rounded-lg text-[#77736E] hover:bg-[#FFF0E4] dark:hover:bg-white/10 hover:text-[#E87524] transition-colors"
                                           title="Edit Operative Profile">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </a>

                                        <button type="button"
                                                @click="resetModalOpen = true; activeProfileId = {{ $p->id }}; activeBadge = '{{ $p->badge_number }}'"
                                                class="p-1.5 rounded-lg text-[#77736E] hover:bg-[#D99024]/15 hover:text-[#D99024] transition-colors"
                                                title="Reset Password">
                                            <i data-lucide="key" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-[#77736E]">
                                    <i data-lucide="user-x" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                    <p class="font-bold">No personnel found.</p>
                                    <p class="text-[11px] mt-1">Adjust your search filters or click "Register Personnel" to add one.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($personnel->hasPages())
                <div class="p-4 border-t border-[#E6E3DE] dark:border-white/10 bg-[#F5F3EF]/60 dark:bg-white/5">
                    {{ $personnel->withQueryString()->links() }}
                </div>
            @endif

            {{-- Reset Password Modal --}}
            <div x-show="resetModalOpen"
                 x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/65 backdrop-blur-xs"
                 @keydown.escape.window="resetModalOpen = false">
                <div class="w-full max-w-md alerto-card shadow-xl p-6" @click.away="resetModalOpen = false">
                    <h3 class="text-base font-bold mb-1">Reset Personnel Password</h3>
                    <p class="text-xs text-[#77736E] mb-4">Set a secure temporary password for Badge #<span class="font-mono font-bold" x-text="activeBadge"></span>.</p>

                    <form :action="`{{ url('admin/personnel') }}/${activeProfileId}/reset-password`" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold mb-1">New Password (min 8 chars)</label>
                            <input type="password" name="password" required minlength="8"
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]"
                                   placeholder="Enter new strong password">
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button type="button" @click="resetModalOpen = false" class="px-4 py-2 text-xs font-bold text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10 rounded-xl">
                                Cancel
                            </button>
                            <button type="submit" class="alerto-btn-press px-4 py-2 text-xs font-bold bg-[#E87524] text-white hover:bg-[#B94F12] rounded-xl shadow-xs">
                                Confirm Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-layouts.admin>
