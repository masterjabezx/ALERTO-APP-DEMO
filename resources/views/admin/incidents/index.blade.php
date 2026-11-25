<x-layouts.admin>
    <div class="space-y-6">

        {{-- Top Bar & Title --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#173957]/10 text-[#173957] text-[11px] font-bold uppercase tracking-wider mb-1">
                    <i data-lucide="shield-alert" class="w-3.5 h-3.5 text-[#3187D8]"></i>
                    Emergency Operations Center · Hinoba-an
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                    Incident Management &amp; Dispatch
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Centralized intake review, triage verification, automated routing, and response unit mobilization.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.incidents.evidence') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#173957] hover:bg-[#0f2438] text-white font-bold text-xs shadow-xs transition-all">
                    <i data-lucide="images" class="w-4 h-4 text-[#3187D8]"></i>
                    <span>Evidence Viewer</span>
                </a>
                <a href="{{ route('admin.incidents.index', ['tab' => 'pending_review']) }}" 
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#DB4545] hover:bg-[#c93a3a] text-white font-bold text-xs shadow-lg shadow-[#DB4545]/25 transition-all">
                    <i data-lucide="alert-circle" class="w-4 h-4 animate-pulse"></i>
                    <span>Pending Triage ({{ $stats['pending_review'] }})</span>
                </a>
            </div>
        </div>

        {{-- KPI Quick Metrics Bar (Real Database Counts) --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
            <div class="eoc-kpi-card p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total</span>
                    <i data-lucide="layers" class="w-4 h-4 text-slate-400"></i>
                </div>
                <p class="text-2xl font-black text-slate-900">{{ number_format($stats['total']) }}</p>
                <span class="text-[10px] text-slate-400">All recorded</span>
            </div>

            <div class="eoc-kpi-card p-4 rounded-2xl bg-amber-50/70 border border-amber-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Pending Review</span>
                    <i data-lucide="clock" class="w-4 h-4 text-amber-600"></i>
                </div>
                <p class="text-2xl font-black text-amber-900">{{ number_format($stats['pending_review']) }}</p>
                <span class="text-[10px] text-amber-700/80">Requires intake</span>
            </div>

            <div class="eoc-kpi-card p-4 rounded-2xl bg-blue-50/70 border border-blue-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-blue-700">Accepted</span>
                    <i data-lucide="check-circle" class="w-4 h-4 text-blue-600"></i>
                </div>
                <p class="text-2xl font-black text-blue-900">{{ number_format($stats['accepted']) }}</p>
                <span class="text-[10px] text-blue-700/80">Ready for dispatch</span>
            </div>

            <div class="eoc-kpi-card p-4 rounded-2xl bg-purple-50/70 border border-purple-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-purple-700">Dispatched</span>
                    <i data-lucide="send" class="w-4 h-4 text-purple-600"></i>
                </div>
                <p class="text-2xl font-black text-purple-900">{{ number_format($stats['dispatched']) }}</p>
                <span class="text-[10px] text-purple-700/80">Units mobilized</span>
            </div>

            <div class="eoc-kpi-card p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Active</span>
                    <i data-lucide="activity" class="w-4 h-4 text-emerald-600"></i>
                </div>
                <p class="text-2xl font-black text-emerald-900">{{ number_format($stats['active_incidents']) }}</p>
                <span class="text-[10px] text-emerald-700/80">On scene &amp; progress</span>
            </div>

            <div class="eoc-kpi-card p-4 rounded-2xl bg-slate-50 border border-slate-200 shadow-xs">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600">Resolved</span>
                    <i data-lucide="shield-check" class="w-4 h-4 text-slate-500"></i>
                </div>
                <p class="text-2xl font-black text-slate-800">{{ number_format($stats['resolved']) }}</p>
                <span class="text-[10px] text-slate-500">Cleared &amp; closed</span>
            </div>
        </div>

        {{-- 8 Navigation Sub-Sections (Tabs) --}}
        <div class="border-b border-slate-200 bg-white rounded-2xl shadow-xs px-2 pt-2">
            <div class="flex items-center gap-1 overflow-x-auto alerto-scrollbar pb-2">
                @php
                    $tabs = [
                        ['id' => 'overview', 'label' => 'Incident Overview', 'icon' => 'layout-grid', 'count' => null],
                        ['id' => 'pending_review', 'label' => 'Pending Review', 'icon' => 'clock', 'count' => $stats['pending_review'], 'badge_color' => 'bg-amber-100 text-amber-800'],
                        ['id' => 'accepted', 'label' => 'Accepted Incidents', 'icon' => 'check-circle-2', 'count' => $stats['accepted'], 'badge_color' => 'bg-blue-100 text-blue-800'],
                        ['id' => 'dispatched', 'label' => 'Dispatched Incidents', 'icon' => 'send', 'count' => $stats['dispatched'], 'badge_color' => 'bg-purple-100 text-purple-800'],
                        ['id' => 'active', 'label' => 'Active Incidents', 'icon' => 'activity', 'count' => $stats['active_incidents'], 'badge_color' => 'bg-emerald-100 text-emerald-800'],
                        ['id' => 'resolved', 'label' => 'Resolved Incidents', 'icon' => 'check-check', 'count' => $stats['resolved'], 'badge_color' => 'bg-slate-100 text-slate-700'],
                        ['id' => 'rejected', 'label' => 'Rejected Incidents', 'icon' => 'x-circle', 'count' => $stats['rejected'], 'badge_color' => 'bg-rose-100 text-rose-800'],
                        ['id' => 'all', 'label' => 'All Incident Records', 'icon' => 'archive', 'count' => $stats['total'], 'badge_color' => 'bg-slate-100 text-slate-600'],
                    ];
                @endphp

                @foreach($tabs as $tab)
                    @php $isCurrent = ($currentTab === $tab['id']); @endphp
                    <a href="{{ route('admin.incidents.index', array_merge($filters, ['tab' => $tab['id']])) }}"
                       class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all {{ $isCurrent ? 'bg-[#173957] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        <i data-lucide="{{ $tab['icon'] }}" class="w-4 h-4 {{ $isCurrent ? 'text-[#3187D8]' : 'text-slate-400' }}"></i>
                        <span>{{ $tab['label'] }}</span>
                        @if($tab['count'] !== null && $tab['count'] > 0)
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold {{ $isCurrent ? 'bg-white/20 text-white' : ($tab['badge_color'] ?? 'bg-slate-200 text-slate-700') }}">
                                {{ $tab['count'] }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Filters & Search Section --}}
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs" x-data="{ expanded: false }">
            <form method="GET" action="{{ route('admin.incidents.index') }}" class="space-y-4">
                <input type="hidden" name="tab" value="{{ $currentTab }}">

                {{-- Primary Search Row --}}
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                    <div class="sm:col-span-6 lg:col-span-5 relative">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ $filters['search'] ?? '' }}" 
                            placeholder="Search by reference (INC-...), caller name, phone, or description..."
                            class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#3187D8] focus:border-[#3187D8] bg-slate-50/50"
                        >
                    </div>

                    <div class="sm:col-span-3 lg:col-span-3">
                        <select name="category_id" class="w-full px-3 py-2.5 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#3187D8] bg-white">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ ($filters['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-3 lg:col-span-2">
                        <select name="priority" class="w-full px-3 py-2.5 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#3187D8] bg-white">
                            <option value="all">All Priorities</option>
                            <option value="critical" {{ ($filters['priority'] ?? '') === 'critical' ? 'selected' : '' }}>🔴 Critical</option>
                            <option value="high" {{ ($filters['priority'] ?? '') === 'high' ? 'selected' : '' }}>🟠 High</option>
                            <option value="medium" {{ ($filters['priority'] ?? '') === 'medium' ? 'selected' : '' }}>🔵 Medium</option>
                            <option value="low" {{ ($filters['priority'] ?? '') === 'low' ? 'selected' : '' }}>⚪ Low</option>
                        </select>
                    </div>

                    <div class="sm:col-span-12 lg:col-span-2 flex items-center gap-2">
                        <button type="submit" class="flex-1 py-2.5 px-4 rounded-xl bg-[#173957] hover:bg-[#0f2438] text-white text-xs font-bold transition-colors flex items-center justify-center gap-2">
                            <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                            <span>Filter</span>
                        </button>
                        <button type="button" x-on:click="expanded = !expanded" class="p-2.5 rounded-xl border border-slate-300 hover:bg-slate-100 text-slate-600 transition-colors" title="Toggle Advanced Filters">
                            <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                        </button>
                        @if(!empty(array_filter($filters, fn($v) => !empty($v) && $v !== 'overview')))
                            <a href="{{ route('admin.incidents.index', ['tab' => $currentTab]) }}" class="p-2.5 rounded-xl text-rose-600 hover:bg-rose-50 border border-rose-200 transition-colors" title="Clear Filters">
                                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Advanced Filters Drawer --}}
                <div x-show="expanded" x-collapse class="pt-3 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Barangay</label>
                        <select name="barangay_id" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white">
                            <option value="">All Hinoba-an Barangays</option>
                            @foreach($barangays as $bg)
                                <option value="{{ $bg->id }}" {{ ($filters['barangay_id'] ?? '') == $bg->id ? 'selected' : '' }}>
                                    {{ $bg->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Contact Verification</label>
                        <select name="verification_status" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white">
                            <option value="all">All Verification Statuses</option>
                            <option value="verified" {{ ($filters['verification_status'] ?? '') === 'verified' ? 'selected' : '' }}>Verified Number</option>
                            <option value="verification_pending" {{ ($filters['verification_status'] ?? '') === 'verification_pending' ? 'selected' : '' }}>Pending Verification</option>
                            <option value="failed_verification" {{ ($filters['verification_status'] ?? '') === 'failed_verification' ? 'selected' : '' }}>Failed Verification</option>
                            <option value="unverified" {{ ($filters['verification_status'] ?? '') === 'unverified' ? 'selected' : '' }}>Unverified</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Assigned Office</label>
                        <select name="office_id" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white">
                            <option value="">All Response Offices</option>
                            @foreach($offices as $off)
                                <option value="{{ $off->id }}" {{ ($filters['office_id'] ?? '') == $off->id ? 'selected' : '' }}>
                                    {{ $off->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Reporting Channel</label>
                        <select name="source_channel" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white">
                            <option value="all">All Channels</option>
                            <option value="citizen_portal" {{ ($filters['source_channel'] ?? '') === 'citizen_portal' ? 'selected' : '' }}>Citizen Portal</option>
                            <option value="facebook_messenger" {{ ($filters['source_channel'] ?? '') === 'facebook_messenger' ? 'selected' : '' }}>Facebook Messenger</option>
                            <option value="admin_encoded" {{ ($filters['source_channel'] ?? '') === 'admin_encoded' ? 'selected' : '' }}>Admin / Landline Encoded</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Date From</label>
                        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Date To</label>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Sort Field</label>
                        <select name="sort_by" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white">
                            <option value="submitted_at" {{ ($filters['sort_by'] ?? '') === 'submitted_at' ? 'selected' : '' }}>Submission Date</option>
                            <option value="reference_number" {{ ($filters['sort_by'] ?? '') === 'reference_number' ? 'selected' : '' }}>Reference Number</option>
                            <option value="priority" {{ ($filters['sort_by'] ?? '') === 'priority' ? 'selected' : '' }}>Priority</option>
                            <option value="status" {{ ($filters['sort_by'] ?? '') === 'status' ? 'selected' : '' }}>Status</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Sort Direction</label>
                        <select name="sort_order" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white">
                            <option value="desc" {{ ($filters['sort_order'] ?? 'desc') === 'desc' ? 'selected' : '' }}>Descending (Newest first)</option>
                            <option value="asc" {{ ($filters['sort_order'] ?? '') === 'asc' ? 'selected' : '' }}>Ascending (Oldest first)</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        {{-- Incident Records Table --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            @if($incidents->isEmpty())
                <div class="p-12 text-center">
                    <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="inbox" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-800">No incident records found</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
                        There are currently no incidents matching the selected tab and filter criteria.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/75 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3.5 px-4">Reference</th>
                                <th class="py-3.5 px-4">Category &amp; Priority</th>
                                <th class="py-3.5 px-4">Location (Hinoba-an)</th>
                                <th class="py-3.5 px-4">Reporter &amp; Contact</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4">Assigned Unit</th>
                                <th class="py-3.5 px-4">Submitted</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            @foreach($incidents as $inc)
                                <tr class="hover:bg-slate-50/80 transition-colors group">
                                    {{-- Reference Number --}}
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <a href="{{ route('admin.incidents.show', $inc->reference_number) }}" 
                                           class="font-mono font-bold text-[#173957] hover:text-[#3187D8] transition-colors inline-flex items-center gap-1.5">
                                            <span>{{ $inc->reference_number }}</span>
                                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                        </a>
                                        <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                            {{ strtoupper(str_replace('_', ' ', $inc->source_channel)) }}
                                        </div>
                                    </td>

                                    {{-- Category & Priority --}}
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-900">{{ $inc->category?->name ?? 'Emergency' }}</div>
                                        <div class="mt-1">
                                            @php
                                                $priorityStyles = [
                                                    'critical' => 'bg-rose-100 text-rose-800 border-rose-200',
                                                    'high' => 'bg-amber-100 text-amber-800 border-amber-200',
                                                    'medium' => 'bg-blue-100 text-blue-800 border-blue-200',
                                                    'low' => 'bg-slate-100 text-slate-700 border-slate-200',
                                                ];
                                            @endphp
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase border {{ $priorityStyles[$inc->priority] ?? 'bg-slate-100 text-slate-700' }}">
                                                @if($inc->priority === 'critical')
                                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                                                @endif
                                                {{ strtoupper($inc->priority) }}
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Location --}}
                                    <td class="py-3.5 px-4">
                                        <div class="font-semibold text-slate-800">{{ $inc->barangay?->name ?? 'Hinoba-an' }}</div>
                                        <div class="text-[11px] text-slate-500 truncate max-w-xs mt-0.5">
                                            {{ $inc->location?->landmark ?? $inc->location?->street_address ?? 'Coordinates Pinned' }}
                                        </div>
                                    </td>

                                    {{-- Reporter & Phone --}}
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="font-medium text-slate-900">{{ $inc->caller_name }}</div>
                                        <div class="text-[11px] font-mono text-slate-600 mt-0.5 flex items-center gap-1.5">
                                            <span>{{ $inc->caller_phone }}</span>
                                            @if($inc->contact_verification_status === 'verified')
                                                <span class="px-1.5 py-0.2 rounded-sm text-[9px] font-bold bg-emerald-100 text-emerald-800" title="Verified Contact">VERIFIED</span>
                                            @elseif($inc->contact_verification_status === 'failed_verification')
                                                <span class="px-1.5 py-0.2 rounded-sm text-[9px] font-bold bg-rose-100 text-rose-800" title="Verification Failed">FAILED</span>
                                            @else
                                                <span class="px-1.5 py-0.2 rounded-sm text-[9px] font-bold bg-amber-100 text-amber-800" title="Unverified Contact">UNVERIFIED</span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Status --}}
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        @php
                                            $statusBadges = [
                                                'pending_review' => ['bg' => 'bg-amber-100 text-amber-800 border-amber-200', 'label' => 'PENDING REVIEW'],
                                                'accepted' => ['bg' => 'bg-blue-100 text-blue-800 border-blue-200', 'label' => 'ACCEPTED'],
                                                'dispatched' => ['bg' => 'bg-purple-100 text-purple-800 border-purple-200', 'label' => 'DISPATCHED'],
                                                'acknowledged' => ['bg' => 'bg-indigo-100 text-indigo-800 border-indigo-200', 'label' => 'ACKNOWLEDGED'],
                                                'on_the_way' => ['bg' => 'bg-cyan-100 text-cyan-800 border-cyan-200', 'label' => 'EN ROUTE'],
                                                'on_scene' => ['bg' => 'bg-emerald-100 text-emerald-800 border-emerald-200', 'label' => 'ON SCENE'],
                                                'in_progress' => ['bg' => 'bg-teal-100 text-teal-800 border-teal-200', 'label' => 'IN PROGRESS'],
                                                'resolved' => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'label' => 'RESOLVED'],
                                                'closed' => ['bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'label' => 'CLOSED'],
                                                'rejected' => ['bg' => 'bg-rose-100 text-rose-800 border-rose-200', 'label' => 'REJECTED'],
                                            ];
                                            $badge = $statusBadges[$inc->status] ?? ['bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'label' => strtoupper($inc->status)];
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold border {{ $badge['bg'] }}">
                                            {{ $badge['label'] }}
                                        </span>
                                    </td>

                                    {{-- Assigned Unit --}}
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        @if($inc->activeAssignments->isNotEmpty())
                                            @php $assignment = $inc->activeAssignments->first(); @endphp
                                            <div class="font-medium text-slate-900">{{ $assignment->office?->name ?? 'Designated Office' }}</div>
                                            <div class="text-[10px] text-slate-500">
                                                {{ $assignment->team?->team_name ?? ($assignment->personnel?->rank_designation ?? 'Assigned Unit') }}
                                            </div>
                                        @else
                                            <span class="text-[11px] text-slate-400 italic">Unassigned</span>
                                        @endif
                                    </td>

                                    {{-- Submitted Date --}}
                                    <td class="py-3.5 px-4 whitespace-nowrap text-slate-600">
                                        <div>{{ $inc->submitted_at?->format('M d, Y') }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ $inc->submitted_at?->format('H:i') }} ({{ $inc->submitted_at?->diffForHumans() }})</div>
                                    </td>

                                    {{-- Action Button --}}
                                    <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                        <a href="{{ route('admin.incidents.show', $inc->reference_number) }}" 
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $inc->status === 'pending_review' ? 'bg-[#DB4545] text-white hover:bg-[#c93a3a] shadow-sm' : ($inc->status === 'accepted' ? 'bg-[#3187D8] text-white hover:bg-[#256fb4]' : 'bg-slate-100 hover:bg-slate-200 text-slate-700') }}">
                                            @if($inc->status === 'pending_review')
                                                <span>Review &amp; Triage</span>
                                            @elseif($inc->status === 'accepted')
                                                <span>Authorize Dispatch</span>
                                            @else
                                                <span>View Details</span>
                                            @endif
                                            <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination Links --}}
                @if($incidents->hasPages())
                    <div class="p-4 border-t border-slate-200 bg-slate-50/50">
                        {{ $incidents->links() }}
                    </div>
                @endif
            @endif
        </div>

    </div>
</x-layouts.admin>

