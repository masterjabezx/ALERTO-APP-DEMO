<x-layouts.admin>
    <div class="space-y-6">

        {{-- Top Bar & Title --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#173957]/10 text-[#173957] text-[11px] font-bold uppercase tracking-wider mb-1">
                    <i data-lucide="building-2" class="w-3.5 h-3.5 text-[#3187D8]"></i>
                    Emergency Operations Center · Hinoba-an
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                    Office Management &amp; Department Agencies
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Manage municipal response agencies, department permissions, personnel allocations, and agency jurisdictions.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.offices.create') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#173957] hover:bg-[#1f4b72] text-white font-bold text-xs shadow-md transition-all">
                    <i data-lucide="plus" class="w-4 h-4 text-[#3187D8]"></i>
                    <span>Register Office Agency</span>
                </a>
            </div>
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

        {{-- KPI Quick Metrics Bar --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
            <div class="eoc-kpi-card p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Agencies</span>
                    <i data-lucide="building-2" class="w-4 h-4 text-slate-400"></i>
                </div>
                <p class="text-2xl font-black text-slate-900">{{ number_format($stats['total_offices']) }}</p>
                <span class="text-[10px] text-slate-400">Registered offices</span>
            </div>

            <div class="eoc-kpi-card p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Active Agencies</span>
                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                </div>
                <p class="text-2xl font-black text-emerald-900">{{ number_format($stats['active_offices']) }}</p>
                <span class="text-[10px] text-emerald-700/80">Operational in system</span>
            </div>

            <div class="eoc-kpi-card p-4 rounded-2xl bg-amber-50/70 border border-amber-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Office Admins</span>
                    <i data-lucide="shield-check" class="w-4 h-4 text-amber-600"></i>
                </div>
                <p class="text-2xl font-black text-amber-900">{{ number_format($stats['office_admins_count']) }}</p>
                <span class="text-[10px] text-amber-700/80">Authorized commanders</span>
            </div>

            <div class="eoc-kpi-card p-4 rounded-2xl bg-blue-50/70 border border-blue-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-blue-700">Office Staff</span>
                    <i data-lucide="users" class="w-4 h-4 text-blue-600"></i>
                </div>
                <p class="text-2xl font-black text-blue-900">{{ number_format($stats['office_staff_count']) }}</p>
                <span class="text-[10px] text-blue-700/80">Operations personnel</span>
            </div>

            <div class="eoc-kpi-card p-4 rounded-2xl bg-purple-50/70 border border-purple-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-purple-700">Online Staff</span>
                    <i data-lucide="activity" class="w-4 h-4 text-purple-600"></i>
                </div>
                <p class="text-2xl font-black text-purple-900">{{ number_format($stats['online_office_users']) }}</p>
                <span class="text-[10px] text-purple-700/80">Active EOC presence</span>
            </div>
        </div>

        {{-- Filter Bar --}}
        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
            <form method="GET" action="{{ route('admin.offices.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Search Agencies</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" 
                           placeholder="Agency name, code, phone, email..." 
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Department Category</label>
                    <select name="department_type" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                        <option value="">All Categories</option>
                        <option value="disaster" {{ ($filters['department_type'] ?? '') == 'disaster' ? 'selected' : '' }}>Disaster Management (MDRRMO)</option>
                        <option value="fire" {{ ($filters['department_type'] ?? '') == 'fire' ? 'selected' : '' }}>Fire Protection (BFP)</option>
                        <option value="police" {{ ($filters['department_type'] ?? '') == 'police' ? 'selected' : '' }}>Law Enforcement (PNP)</option>
                        <option value="medical" {{ ($filters['department_type'] ?? '') == 'medical' ? 'selected' : '' }}>Health &amp; Medical (RHU)</option>
                        <option value="traffic" {{ ($filters['department_type'] ?? '') == 'traffic' ? 'selected' : '' }}>Traffic &amp; Public Safety</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Operational Status</label>
                    <select name="is_active" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                        <option value="">All Statuses</option>
                        <option value="1" {{ ($filters['is_active'] ?? '') === '1' ? 'selected' : '' }}>Active Only</option>
                        <option value="0" {{ ($filters['is_active'] ?? '') === '0' ? 'selected' : '' }}>Deactivated Only</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 py-2 px-3 bg-[#173957] text-white font-bold text-xs rounded-xl hover:bg-[#1f4b72] transition-colors">
                        Apply Filters
                    </button>
                    @if(!empty(array_filter($filters)))
                        <a href="{{ route('admin.offices.index') }}" class="py-2 px-3 bg-slate-100 text-slate-600 font-bold text-xs rounded-xl hover:bg-slate-200 transition-colors">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Offices Data Table --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-[10px] tracking-wider font-bold">
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
                    <tbody class="divide-y divide-slate-100">
                        @forelse($offices as $office)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 text-sm">{{ $office->name }}</div>
                                    <div class="font-mono text-[11px] text-slate-500">{{ $office->code }}</div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold uppercase
                                        {{ $office->department_type === 'fire' ? 'bg-orange-100 text-orange-800' : '' }}
                                        {{ $office->department_type === 'medical' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                        {{ $office->department_type === 'police' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $office->department_type === 'disaster' ? 'bg-purple-100 text-purple-800' : '' }}
                                        {{ $office->department_type === 'traffic' ? 'bg-amber-100 text-amber-800' : '' }}">
                                        {{ ucfirst($office->department_type) }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-slate-600">
                                    <div class="font-mono font-medium">{{ $office->contact_phone }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $office->contact_email ?? 'No official email' }}</div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-800">{{ $office->location?->barangay?->name ?? 'Hinoba-an' }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $office->location?->street_address ?? 'Poblacion' }}</div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse($office->teams as $team)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                {{ $team->call_sign }}
                                            </span>
                                        @empty
                                            <span class="text-slate-400 italic text-[11px]">No teams</span>
                                        @endforelse
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 text-slate-600">
                                    <div class="font-bold text-slate-800">{{ $office->users_count }} Account(s)</div>
                                    <div class="text-[10px] text-slate-400">{{ $office->personnel_count }} Field Operative(s)</div>
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <form method="POST" action="{{ route('admin.offices.toggle-status', $office->id) }}" onsubmit="return confirm('Toggle status for this agency?');">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded-full text-[10px] font-bold transition-all {{ $office->is_active ? 'bg-emerald-100 hover:bg-emerald-200 text-emerald-800' : 'bg-rose-100 hover:bg-rose-200 text-rose-800' }}">
                                            {{ $office->is_active ? 'Active' : 'Disabled' }}
                                        </button>
                                    </form>
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="{{ route('admin.offices.users', $office->id) }}" 
                                           class="px-2.5 py-1.5 rounded-lg text-xs font-bold text-[#173957] hover:bg-slate-100 transition-colors"
                                           title="Manage Office Accounts">
                                            Accounts ({{ $office->users_count }})
                                        </a>

                                        <a href="{{ route('admin.offices.edit', $office->id) }}" 
                                           class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition-colors"
                                           title="Edit Agency Configuration">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    <i data-lucide="building" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                    <p class="font-bold text-slate-600">No office agencies found.</p>
                                    <p class="text-xs text-slate-400 mt-1">Click "Register Office Agency" above to configure department agencies.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($offices->hasPages())
                <div class="p-4 border-t border-slate-200 bg-slate-50">
                    {{ $offices->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layouts.admin>
