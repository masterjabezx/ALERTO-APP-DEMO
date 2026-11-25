<x-layouts.admin :title="'Office Agencies — ALERTO EOC'">
    <div class="space-y-6">

        {{-- Top Bar & Title --}}
        <x-portal.page-header
            badge="Emergency Operations Center · Hinoba-an"
            title="Office Management &amp; Department Agencies"
            subtitle="Manage municipal response agencies, department permissions, personnel allocations, and agency jurisdictions."
        >
            <x-slot:actions>
                <a href="{{ route('admin.offices.create') }}"
                   class="alerto-btn-press inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white font-bold text-xs shadow-md transition-all">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Register Office Agency</span>
                </a>
            </x-slot:actions>
        </x-portal.page-header>

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

        {{-- KPI Quick Metrics Bar --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
            <div class="alerto-kpi-card alerto-card p-4">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#77736E]">Total Agencies</span>
                    <i data-lucide="building-2" class="w-4 h-4 text-[#77736E]"></i>
                </div>
                <p class="text-2xl font-black font-mono">{{ number_format($stats['total_offices']) }}</p>
                <span class="text-[10px] text-[#77736E]">Registered offices</span>
            </div>

            <div class="alerto-kpi-card alerto-card p-4 border-[#27845B]/30">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#27845B]">Active Agencies</span>
                    <i data-lucide="check-circle" class="w-4 h-4 text-[#27845B]"></i>
                </div>
                <p class="text-2xl font-black font-mono text-[#27845B]">{{ number_format($stats['active_offices']) }}</p>
                <span class="text-[10px] text-[#77736E]">Operational in system</span>
            </div>

            <div class="alerto-kpi-card alerto-card p-4 border-[#E87524]/30">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#B94F12] dark:text-[#F29A56]">Office Admins</span>
                    <i data-lucide="shield-check" class="w-4 h-4 text-[#E87524]"></i>
                </div>
                <p class="text-2xl font-black font-mono text-[#E87524]">{{ number_format($stats['office_admins_count']) }}</p>
                <span class="text-[10px] text-[#77736E]">Authorized commanders</span>
            </div>

            <div class="alerto-kpi-card alerto-card p-4 border-[#397BA8]/30">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#397BA8]">Office Staff</span>
                    <i data-lucide="users" class="w-4 h-4 text-[#397BA8]"></i>
                </div>
                <p class="text-2xl font-black font-mono text-[#397BA8]">{{ number_format($stats['office_staff_count']) }}</p>
                <span class="text-[10px] text-[#77736E]">Operations personnel</span>
            </div>

            <div class="alerto-kpi-card alerto-card p-4 border-[#D99024]/30">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#D99024]">Online Staff</span>
                    <i data-lucide="activity" class="w-4 h-4 text-[#D99024]"></i>
                </div>
                <p class="text-2xl font-black font-mono text-[#D99024]">{{ number_format($stats['online_office_users']) }}</p>
                <span class="text-[10px] text-[#77736E]">Active EOC presence</span>
            </div>
        </div>

        {{-- Filter Bar --}}
        <div class="alerto-card p-4">
            <form method="GET" action="{{ route('admin.offices.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-[#77736E] mb-1">Search Agencies</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="Agency name, code, phone, email..."
                           class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-[#77736E] mb-1">Department Category</label>
                    <select name="department_type" class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                        <option value="">All Categories</option>
                        <option value="disaster" {{ ($filters['department_type'] ?? '') == 'disaster' ? 'selected' : '' }}>Disaster Management (MDRRMO)</option>
                        <option value="fire" {{ ($filters['department_type'] ?? '') == 'fire' ? 'selected' : '' }}>Fire Protection (BFP)</option>
                        <option value="police" {{ ($filters['department_type'] ?? '') == 'police' ? 'selected' : '' }}>Law Enforcement (PNP)</option>
                        <option value="medical" {{ ($filters['department_type'] ?? '') == 'medical' ? 'selected' : '' }}>Health &amp; Medical (RHU)</option>
                        <option value="traffic" {{ ($filters['department_type'] ?? '') == 'traffic' ? 'selected' : '' }}>Traffic &amp; Public Safety</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-[#77736E] mb-1">Operational Status</label>
                    <select name="is_active" class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                        <option value="">All Statuses</option>
                        <option value="1" {{ ($filters['is_active'] ?? '') === '1' ? 'selected' : '' }}>Active Only</option>
                        <option value="0" {{ ($filters['is_active'] ?? '') === '0' ? 'selected' : '' }}>Deactivated Only</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="alerto-btn-press flex-1 py-2 px-3 bg-[#E87524] hover:bg-[#B94F12] text-white font-bold text-xs rounded-xl transition-colors">
                        Apply Filters
                    </button>
                    @if(!empty(array_filter($filters)))
                        <a href="{{ route('admin.offices.index') }}" class="py-2 px-3 bg-[#F5F3EF] dark:bg-white/10 text-[#454A50] dark:text-[#CFCBC4] font-bold text-xs rounded-xl hover:bg-[#E6E3DE] dark:hover:bg-white/20 transition-colors">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Offices Data Table --}}
        <div class="alerto-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F5F3EF] dark:bg-white/5 border-b border-[#E6E3DE] dark:border-white/10 text-[#77736E] uppercase text-[10px] tracking-wider font-bold">
                        <tr>
                            <th class="py-3 px-4">Office Agency &amp; Code</th>
                            <th class="py-3 px-4">Department Category</th>
                            <th class="py-3 px-4">Contact Info</th>
                            <th class="py-3 px-4">Location / Barangay</th>
                            <th class="py-3 px-4">Response Teams</th>
                            <th class="py-3 px-4">Staff &amp; Responders</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E6E3DE] dark:divide-white/10">
                        @forelse($offices as $office)
                            <tr class="hover:bg-[#FFF0E4]/40 dark:hover:bg-white/5 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-sm">{{ $office->name }}</div>
                                    <div class="font-mono text-[11px] text-[#77736E]">{{ $office->code }}</div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold uppercase
                                        {{ $office->department_type === 'fire' ? 'bg-[#C83F43]/15 text-[#C83F43]' : '' }}
                                        {{ $office->department_type === 'medical' ? 'bg-[#27845B]/15 text-[#27845B]' : '' }}
                                        {{ $office->department_type === 'police' ? 'bg-[#397BA8]/15 text-[#397BA8]' : '' }}
                                        {{ $office->department_type === 'disaster' ? 'bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56]' : '' }}
                                        {{ $office->department_type === 'traffic' ? 'bg-[#D99024]/15 text-[#D99024]' : '' }}">
                                        {{ ucfirst($office->department_type) }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-[#454A50] dark:text-[#CFCBC4]">
                                    <div class="font-mono font-medium">{{ $office->contact_phone }}</div>
                                    <div class="text-[11px] text-[#77736E]">{{ $office->contact_email ?? 'No official email' }}</div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="font-bold">{{ $office->location?->barangay?->name ?? 'Hinoba-an' }}</div>
                                    <div class="text-[11px] text-[#77736E]">{{ $office->location?->street_address ?? 'Poblacion' }}</div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse($office->teams as $team)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56] border border-[#E87524]/25">
                                                {{ $team->call_sign }}
                                            </span>
                                        @empty
                                            <span class="text-[#77736E] italic text-[11px]">No teams</span>
                                        @endforelse
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 text-[#454A50] dark:text-[#CFCBC4]">
                                    <div class="font-bold">{{ $office->users_count }} Account(s)</div>
                                    <div class="text-[10px] text-[#77736E]">{{ $office->personnel_count }} Field Operative(s)</div>
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <form method="POST" action="{{ route('admin.offices.toggle-status', $office->id) }}" onsubmit="return confirm('Toggle status for this agency?');">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded-full text-[10px] font-bold transition-all {{ $office->is_active ? 'bg-[#27845B]/15 hover:bg-[#27845B]/25 text-[#27845B]' : 'bg-[#C83F43]/15 hover:bg-[#C83F43]/25 text-[#C83F43]' }}">
                                            {{ $office->is_active ? 'Active' : 'Disabled' }}
                                        </button>
                                    </form>
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="{{ route('admin.offices.users', $office->id) }}"
                                           class="px-2.5 py-1.5 rounded-lg text-xs font-bold text-[#B94F12] dark:text-[#F29A56] hover:bg-[#E87524]/15 transition-colors"
                                           title="Manage Office Accounts">
                                            Accounts ({{ $office->users_count }})
                                        </a>

                                        <a href="{{ route('admin.offices.edit', $office->id) }}"
                                           class="p-1.5 rounded-lg text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10 hover:text-[#17191C] dark:hover:text-white transition-colors"
                                           title="Edit Agency Configuration">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-[#77736E]">
                                    <i data-lucide="building" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                    <p class="font-bold">No office agencies found.</p>
                                    <p class="text-xs mt-1">Click "Register Office Agency" above to configure department agencies.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($offices->hasPages())
                <div class="p-4 border-t border-[#E6E3DE] dark:border-white/10 bg-[#F5F3EF]/60 dark:bg-white/5">
                    {{ $offices->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layouts.admin>
