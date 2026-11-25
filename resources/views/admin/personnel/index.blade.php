<x-layouts.admin>
    <div class="space-y-6">

        {{-- Top Bar & Title --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#173957]/10 text-[#173957] text-[11px] font-bold uppercase tracking-wider mb-1">
                    <i data-lucide="users" class="w-3.5 h-3.5 text-[#3187D8]"></i>
                    Emergency Operations Center · Hinoba-an
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                    Personnel Management &amp; Response Units
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Field operatives directory, emergency teams, operational availability, jurisdictions, and response bases.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.personnel.create') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#173957] hover:bg-[#1f4b72] text-white font-bold text-xs shadow-md transition-all">
                    <i data-lucide="user-plus" class="w-4 h-4 text-[#3187D8]"></i>
                    <span>Register Personnel</span>
                </a>
            </div>
        </div>

        {{-- Sub-navigation Tabs --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-slate-200">
            <a href="{{ route('admin.personnel.index') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.index') ? 'bg-[#173957] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <i data-lucide="users" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Directory ({{ $stats['total'] }})
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
                Active Assignments ({{ $stats['active_assignments'] }})
            </a>
            <a href="{{ route('admin.personnel.bases') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.bases') ? 'bg-[#173957] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <i data-lucide="home" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Response Bases
            </a>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center gap-3">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium flex items-center gap-3">
                <i data-lucide="alert-octagon" class="w-4 h-4 text-rose-600 shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- KPI Quick Metrics Bar (Real Database Counts) --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
            <div class="eoc-kpi-card p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Personnel</span>
                    <i data-lucide="users" class="w-4 h-4 text-slate-400"></i>
                </div>
                <p class="text-2xl font-black text-slate-900">{{ number_format($stats['total']) }}</p>
                <span class="text-[10px] text-slate-400">Registered staff</span>
            </div>

            <div class="eoc-kpi-card p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Available</span>
                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                </div>
                <p class="text-2xl font-black text-emerald-900">{{ number_format($stats['available']) }}</p>
                <span class="text-[10px] text-emerald-700/80">Ready for dispatch</span>
            </div>

            <div class="eoc-kpi-card p-4 rounded-2xl bg-blue-50/70 border border-blue-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-blue-700">Dispatched</span>
                    <i data-lucide="send" class="w-4 h-4 text-blue-600"></i>
                </div>
                <p class="text-2xl font-black text-blue-900">{{ number_format($stats['dispatched']) }}</p>
                <span class="text-[10px] text-blue-700/80">En route to scene</span>
            </div>

            <div class="eoc-kpi-card p-4 rounded-2xl bg-purple-50/70 border border-purple-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-purple-700">On Scene</span>
                    <i data-lucide="map-pin" class="w-4 h-4 text-purple-600"></i>
                </div>
                <p class="text-2xl font-black text-purple-900">{{ number_format($stats['on_scene']) }}</p>
                <span class="text-[10px] text-purple-700/80">Conducting operations</span>
            </div>

            <div class="eoc-kpi-card p-4 rounded-2xl bg-slate-50 border border-slate-200 shadow-xs">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600">Off Duty</span>
                    <i data-lucide="moon" class="w-4 h-4 text-slate-500"></i>
                </div>
                <p class="text-2xl font-black text-slate-700">{{ number_format($stats['off_duty']) }}</p>
                <span class="text-[10px] text-slate-500">Standby / off-shift</span>
            </div>

            <div class="eoc-kpi-card p-4 rounded-2xl bg-amber-50/70 border border-amber-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Active Missions</span>
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600"></i>
                </div>
                <p class="text-2xl font-black text-amber-900">{{ number_format($stats['active_assignments']) }}</p>
                <span class="text-[10px] text-amber-700/80">Live emergency runs</span>
            </div>
        </div>

        {{-- Filters Bar --}}
        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
            <form method="GET" action="{{ route('admin.personnel.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Search</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" 
                           placeholder="Badge, name, email, phone..." 
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8] focus:ring-1 focus:ring-[#3187D8]">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Office</label>
                    <select name="office_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                        <option value="">All Offices</option>
                        @foreach($offices as $office)
                            <option value="{{ $office->id }}" {{ ($filters['office_id'] ?? '') == $office->id ? 'selected' : '' }}>
                                {{ $office->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Response Team</label>
                    <select name="team_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                        <option value="">All Teams</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}" {{ ($filters['team_id'] ?? '') == $team->id ? 'selected' : '' }}>
                                {{ $team->team_name }} ({{ $team->call_sign }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Operational Status</label>
                    <select name="operational_status" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                        <option value="">All Statuses</option>
                        <option value="available" {{ ($filters['operational_status'] ?? '') == 'available' ? 'selected' : '' }}>Available (Ready)</option>
                        <option value="dispatched" {{ ($filters['operational_status'] ?? '') == 'dispatched' ? 'selected' : '' }}>Dispatched (En Route)</option>
                        <option value="on_scene" {{ ($filters['operational_status'] ?? '') == 'on_scene' ? 'selected' : '' }}>On Scene</option>
                        <option value="busy" {{ ($filters['operational_status'] ?? '') == 'busy' ? 'selected' : '' }}>Busy</option>
                        <option value="off_duty" {{ ($filters['operational_status'] ?? '') == 'off_duty' ? 'selected' : '' }}>Off Duty</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 py-2 px-3 bg-[#173957] text-white font-bold text-xs rounded-xl hover:bg-[#1f4b72] transition-colors">
                        Apply Filters
                    </button>
                    @if(!empty(array_filter($filters)))
                        <a href="{{ route('admin.personnel.index') }}" class="py-2 px-3 bg-slate-100 text-slate-600 font-bold text-xs rounded-xl hover:bg-slate-200 transition-colors">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Personnel Directory Table --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden" x-data="{ resetModalOpen: false, activeProfileId: null, activeBadge: '' }">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-[10px] tracking-wider font-bold">
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
                    <tbody class="divide-y divide-slate-100">
                        @forelse($personnel as $p)
                            @php
                                $opStatus = $p->availability?->operational_status ?? 'off_duty';
                                $presenceStatus = $p->user?->userPresence?->status ?? 'offline';
                                $isAccountActive = (bool) $p->user?->is_active;
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-[#173957]/10 text-[#173957] font-black text-xs flex items-center justify-center shrink-0">
                                            {{ substr($p->user?->name ?? 'P', 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 flex items-center gap-1.5">
                                                <span>{{ $p->user?->name ?? 'Unknown' }}</span>
                                                @if($p->is_team_leader)
                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-amber-100 text-amber-800">LEADER</span>
                                                @endif
                                            </div>
                                            <div class="text-[11px] font-mono text-slate-500">
                                                #{{ $p->badge_number }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 text-slate-600">
                                    <div>{{ $p->user?->email }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">{{ $p->user?->phone_number ?? 'No phone' }}</div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-800">{{ $p->office?->name ?? 'Unassigned' }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $p->rank_designation }}</div>
                                    @if($p->specialization)
                                        <div class="text-[10px] text-[#3187D8] font-medium">{{ $p->specialization }}</div>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="flex flex-wrap gap-1 mb-1">
                                        @forelse($p->teams as $team)
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                {{ $team->call_sign }}
                                            </span>
                                        @empty
                                            <span class="text-[11px] text-slate-400 italic">No team</span>
                                        @endforelse
                                    </div>
                                    <div class="text-[10px] text-slate-400">
                                        {{ $p->jurisdictions->count() }} barangay(s)
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    @if($opStatus === 'available')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> Available
                                        </span>
                                    @elseif($opStatus === 'dispatched')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span> Dispatched
                                        </span>
                                    @elseif($opStatus === 'on_scene')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-purple-600"></span> On Scene
                                        </span>
                                    @elseif($opStatus === 'busy')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span> Busy
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Off Duty
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4">
                                    @if($presenceStatus === 'online')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Online
                                        </span>
                                    @elseif($presenceStatus === 'connection_lost')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700">
                                            <span class="w-2 h-2 rounded-full bg-rose-500"></span> Lost
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-slate-50 text-slate-500">
                                            <span class="w-2 h-2 rounded-full bg-slate-300"></span> Offline
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <form method="POST" action="{{ route('admin.personnel.toggle-status', $p->id) }}" onsubmit="return confirm('Toggle active status for this personnel?');">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded-full text-[10px] font-bold transition-all {{ $isAccountActive ? 'bg-emerald-100 hover:bg-emerald-200 text-emerald-800' : 'bg-rose-100 hover:bg-rose-200 text-rose-800' }}">
                                            {{ $isAccountActive ? 'Active' : 'Disabled' }}
                                        </button>
                                    </form>
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="{{ route('admin.personnel.edit', $p->id) }}" 
                                           class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition-colors"
                                           title="Edit Operative Profile">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </a>

                                        <button type="button" 
                                                @click="resetModalOpen = true; activeProfileId = {{ $p->id }}; activeBadge = '{{ $p->badge_number }}'"
                                                class="p-1.5 rounded-lg text-slate-500 hover:bg-amber-50 hover:text-amber-700 transition-colors"
                                                title="Reset Password">
                                            <i data-lucide="key" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    <i data-lucide="user-x" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                    <p class="font-bold text-slate-600">No personnel found.</p>
                                    <p class="text-[11px] text-slate-400 mt-1">Adjust your search filters or click "Register Personnel" to add one.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($personnel->hasPages())
                <div class="p-4 border-t border-slate-200 bg-slate-50">
                    {{ $personnel->withQueryString()->links() }}
                </div>
            @endif

            {{-- Reset Password Modal --}}
            <div x-show="resetModalOpen" 
                 x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
                 @keydown.escape.window="resetModalOpen = false">
                <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-6 border border-slate-200" @click.away="resetModalOpen = false">
                    <h3 class="text-base font-bold text-slate-900 mb-1">Reset Personnel Password</h3>
                    <p class="text-xs text-slate-500 mb-4">Set a secure temporary password for Badge #<span class="font-mono font-bold" x-text="activeBadge"></span>.</p>
                    
                    <form :action="`{{ url('admin/personnel') }}/${activeProfileId}/reset-password`" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">New Password (min 8 chars)</label>
                            <input type="password" name="password" required minlength="8" 
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]" 
                                   placeholder="Enter new strong password">
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button type="button" @click="resetModalOpen = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 text-xs font-bold bg-[#173957] text-white hover:bg-[#1f4b72] rounded-xl shadow-xs">
                                Confirm Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-layouts.admin>
