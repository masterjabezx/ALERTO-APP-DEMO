<x-layouts.admin :title="'Incident Management — ALERTO EOC'">
    <div class="space-y-6">

        {{-- Top Bar & Title --}}
        <x-portal.page-header
            badge="Emergency Operations Center · Hinoba-an"
            title="Incident Management &amp; Dispatch"
            subtitle="Centralized intake review, triage verification, automated routing, and response unit mobilization."
        >
            <x-slot:actions>
                <a href="{{ route('admin.incidents.evidence') }}"
                   class="px-4 py-2.5 rounded-xl bg-[#17191C] hover:bg-[#292D32] text-[#F5F3EF] border border-white/15 font-bold text-xs shadow-xs transition-all inline-flex items-center gap-2">
                    <i data-lucide="images" class="w-4 h-4 text-[#E87524]"></i>
                    <span>Evidence Viewer</span>
                </a>
                <a href="{{ route('admin.incidents.index', ['tab' => 'pending_review']) }}"
                   class="alerto-btn-press inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#C83F43] hover:bg-[#A63034] text-white font-bold text-xs shadow-lg shadow-[#C83F43]/25 transition-all">
                    <i data-lucide="alert-circle" class="w-4 h-4 animate-pulse"></i>
                    <span>Pending Triage ({{ $stats['pending_review'] }})</span>
                </a>
            </x-slot:actions>
        </x-portal.page-header>

        {{-- KPI Quick Metrics Bar (Real Database Counts) --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
            <div class="alerto-kpi-card alerto-card p-4">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#77736E]">Total</span>
                    <i data-lucide="layers" class="w-4 h-4 text-[#77736E]"></i>
                </div>
                <p class="text-2xl font-black font-mono">{{ number_format($stats['total']) }}</p>
                <span class="text-[10px] text-[#77736E]">All recorded</span>
            </div>

            <div class="alerto-kpi-card alerto-card p-4 border-[#D99024]/30">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#D99024]">Pending Review</span>
                    <i data-lucide="clock" class="w-4 h-4 text-[#D99024]"></i>
                </div>
                <p class="text-2xl font-black font-mono text-[#D99024]">{{ number_format($stats['pending_review']) }}</p>
                <span class="text-[10px] text-[#77736E]">Requires intake</span>
            </div>

            <div class="alerto-kpi-card alerto-card p-4 border-[#397BA8]/30">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#397BA8]">Accepted</span>
                    <i data-lucide="check-circle" class="w-4 h-4 text-[#397BA8]"></i>
                </div>
                <p class="text-2xl font-black font-mono text-[#397BA8]">{{ number_format($stats['accepted']) }}</p>
                <span class="text-[10px] text-[#77736E]">Ready for dispatch</span>
            </div>

            <div class="alerto-kpi-card alerto-card p-4 border-[#E87524]/30">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#B94F12] dark:text-[#F29A56]">Dispatched</span>
                    <i data-lucide="send" class="w-4 h-4 text-[#E87524]"></i>
                </div>
                <p class="text-2xl font-black font-mono text-[#E87524]">{{ number_format($stats['dispatched']) }}</p>
                <span class="text-[10px] text-[#77736E]">Units mobilized</span>
            </div>

            <div class="alerto-kpi-card alerto-card p-4 border-[#27845B]/30">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#27845B]">Active</span>
                    <i data-lucide="activity" class="w-4 h-4 text-[#27845B]"></i>
                </div>
                <p class="text-2xl font-black font-mono text-[#27845B]">{{ number_format($stats['active_incidents']) }}</p>
                <span class="text-[10px] text-[#77736E]">On scene &amp; progress</span>
            </div>

            <div class="alerto-kpi-card alerto-card p-4">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#77736E]">Resolved</span>
                    <i data-lucide="shield-check" class="w-4 h-4 text-[#77736E]"></i>
                </div>
                <p class="text-2xl font-black font-mono">{{ number_format($stats['resolved']) }}</p>
                <span class="text-[10px] text-[#77736E]">Cleared &amp; closed</span>
            </div>
        </div>

        {{-- 8 Navigation Sub-Sections (Tabs) --}}
        <div class="alerto-card px-2 pt-2">
            <div class="flex items-center gap-1 overflow-x-auto alerto-scrollbar pb-2">
                @php
                    $tabs = [
                        ['id' => 'overview', 'label' => 'Incident Overview', 'icon' => 'layout-grid', 'count' => null],
                        ['id' => 'pending_review', 'label' => 'Pending Review', 'icon' => 'clock', 'count' => $stats['pending_review'], 'badge_color' => 'bg-[#D99024]/15 text-[#B94F12] dark:text-[#F29A56]'],
                        ['id' => 'accepted', 'label' => 'Accepted Incidents', 'icon' => 'check-circle-2', 'count' => $stats['accepted'], 'badge_color' => 'bg-[#397BA8]/15 text-[#397BA8]'],
                        ['id' => 'dispatched', 'label' => 'Dispatched Incidents', 'icon' => 'send', 'count' => $stats['dispatched'], 'badge_color' => 'bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56]'],
                        ['id' => 'active', 'label' => 'Active Incidents', 'icon' => 'activity', 'count' => $stats['active_incidents'], 'badge_color' => 'bg-[#27845B]/15 text-[#27845B]'],
                        ['id' => 'resolved', 'label' => 'Resolved Incidents', 'icon' => 'check-check', 'count' => $stats['resolved'], 'badge_color' => 'bg-black/5 dark:bg-white/10 text-[#77736E]'],
                        ['id' => 'rejected', 'label' => 'Rejected Incidents', 'icon' => 'x-circle', 'count' => $stats['rejected'], 'badge_color' => 'bg-[#C83F43]/15 text-[#C83F43]'],
                        ['id' => 'all', 'label' => 'All Incident Records', 'icon' => 'archive', 'count' => $stats['total'], 'badge_color' => 'bg-black/5 dark:bg-white/10 text-[#77736E]'],
                    ];
                @endphp

                @foreach($tabs as $tab)
                    @php $isCurrent = ($currentTab === $tab['id']); @endphp
                    <a href="{{ route('admin.incidents.index', array_merge($filters, ['tab' => $tab['id']])) }}"
                       class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all {{ $isCurrent ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                        <i data-lucide="{{ $tab['icon'] }}" class="w-4 h-4 {{ $isCurrent ? 'text-white' : 'text-[#77736E]' }}"></i>
                        <span>{{ $tab['label'] }}</span>
                        @if($tab['count'] !== null && $tab['count'] > 0)
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold {{ $isCurrent ? 'bg-white/20 text-white' : ($tab['badge_color'] ?? 'bg-black/10 text-[#77736E]') }}">
                                {{ $tab['count'] }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Filters & Search Section --}}
        <div class="alerto-card p-4 sm:p-5" x-data="{ expanded: false }">
            <form method="GET" action="{{ route('admin.incidents.index') }}" class="space-y-4">
                <input type="hidden" name="tab" value="{{ $currentTab }}">

                {{-- Primary Search Row --}}
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                    <div class="sm:col-span-6 lg:col-span-5 relative">
                        <i data-lucide="search" class="w-4 h-4 text-[#77736E] absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                        <input
                            type="text"
                            name="search"
                            value="{{ $filters['search'] ?? '' }}"
                            placeholder="Search by reference (INC-...), caller name, phone, or description..."
                            class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 focus:outline-hidden focus:ring-2 focus:ring-[#E87524] bg-[#F5F3EF]/60 dark:bg-[#17191C]"
                        >
                    </div>

                    <div class="sm:col-span-3 lg:col-span-3">
                        <select name="category_id" class="w-full px-3 py-2.5 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 focus:outline-hidden focus:ring-2 focus:ring-[#E87524] bg-white dark:bg-[#17191C]">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ ($filters['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-3 lg:col-span-2">
                        <select name="priority" class="w-full px-3 py-2.5 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 focus:outline-hidden focus:ring-2 focus:ring-[#E87524] bg-white dark:bg-[#17191C]">
                            <option value="all">All Priorities</option>
                            <option value="critical" {{ ($filters['priority'] ?? '') === 'critical' ? 'selected' : '' }}>Critical</option>
                            <option value="high" {{ ($filters['priority'] ?? '') === 'high' ? 'selected' : '' }}>High</option>
                            <option value="medium" {{ ($filters['priority'] ?? '') === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="low" {{ ($filters['priority'] ?? '') === 'low' ? 'selected' : '' }}>Low</option>
                        </select>
                    </div>

                    <div class="sm:col-span-12 lg:col-span-2 flex items-center gap-2">
                        <button type="submit" class="alerto-btn-press flex-1 py-2.5 px-4 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white text-xs font-bold transition-colors flex items-center justify-center gap-2 shadow-xs">
                            <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                            <span>Filter</span>
                        </button>
                        <button type="button" x-on:click="expanded = !expanded" class="p-2.5 rounded-xl border border-[#E6E3DE] dark:border-white/15 hover:bg-[#F5F3EF] dark:hover:bg-white/10 text-[#454A50] dark:text-[#CFCBC4] transition-colors" title="Toggle Advanced Filters">
                            <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                        </button>
                        @if(!empty(array_filter($filters, fn($v) => !empty($v) && $v !== 'overview')))
                            <a href="{{ route('admin.incidents.index', ['tab' => $currentTab]) }}" class="p-2.5 rounded-xl text-[#C83F43] hover:bg-[#C83F43]/10 border border-[#C83F43]/30 transition-colors" title="Clear Filters">
                                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Advanced Filters Drawer --}}
                <div x-show="expanded" x-collapse class="pt-3 border-t border-[#E6E3DE] dark:border-white/10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-[#454A50] dark:text-[#9E9993] uppercase mb-1">Barangay</label>
                        <select name="barangay_id" class="w-full px-3 py-2 text-xs rounded-lg border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                            <option value="">All Hinoba-an Barangays</option>
                            @foreach($barangays as $bg)
                                <option value="{{ $bg->id }}" {{ ($filters['barangay_id'] ?? '') == $bg->id ? 'selected' : '' }}>
                                    {{ $bg->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-[#454A50] dark:text-[#9E9993] uppercase mb-1">Contact Verification</label>
                        <select name="verification_status" class="w-full px-3 py-2 text-xs rounded-lg border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                            <option value="all">All Verification Statuses</option>
                            <option value="verified" {{ ($filters['verification_status'] ?? '') === 'verified' ? 'selected' : '' }}>Verified Number</option>
                            <option value="verification_pending" {{ ($filters['verification_status'] ?? '') === 'verification_pending' ? 'selected' : '' }}>Pending Verification</option>
                            <option value="failed_verification" {{ ($filters['verification_status'] ?? '') === 'failed_verification' ? 'selected' : '' }}>Failed Verification</option>
                            <option value="unverified" {{ ($filters['verification_status'] ?? '') === 'unverified' ? 'selected' : '' }}>Unverified</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-[#454A50] dark:text-[#9E9993] uppercase mb-1">Assigned Office</label>
                        <select name="office_id" class="w-full px-3 py-2 text-xs rounded-lg border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                            <option value="">All Response Offices</option>
                            @foreach($offices as $off)
                                <option value="{{ $off->id }}" {{ ($filters['office_id'] ?? '') == $off->id ? 'selected' : '' }}>
                                    {{ $off->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-[#454A50] dark:text-[#9E9993] uppercase mb-1">Reporting Channel</label>
                        <select name="source_channel" class="w-full px-3 py-2 text-xs rounded-lg border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                            <option value="all">All Channels</option>
                            <option value="citizen_portal" {{ ($filters['source_channel'] ?? '') === 'citizen_portal' ? 'selected' : '' }}>Citizen Portal</option>
                            <option value="facebook_messenger" {{ ($filters['source_channel'] ?? '') === 'facebook_messenger' ? 'selected' : '' }}>Facebook Messenger</option>
                            <option value="admin_encoded" {{ ($filters['source_channel'] ?? '') === 'admin_encoded' ? 'selected' : '' }}>Admin / Landline Encoded</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-[#454A50] dark:text-[#9E9993] uppercase mb-1">Date From</label>
                        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full px-3 py-2 text-xs rounded-lg border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-[#454A50] dark:text-[#9E9993] uppercase mb-1">Date To</label>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full px-3 py-2 text-xs rounded-lg border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-[#454A50] dark:text-[#9E9993] uppercase mb-1">Sort Field</label>
                        <select name="sort_by" class="w-full px-3 py-2 text-xs rounded-lg border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                            <option value="submitted_at" {{ ($filters['sort_by'] ?? '') === 'submitted_at' ? 'selected' : '' }}>Submission Date</option>
                            <option value="reference_number" {{ ($filters['sort_by'] ?? '') === 'reference_number' ? 'selected' : '' }}>Reference Number</option>
                            <option value="priority" {{ ($filters['sort_by'] ?? '') === 'priority' ? 'selected' : '' }}>Priority</option>
                            <option value="status" {{ ($filters['sort_by'] ?? '') === 'status' ? 'selected' : '' }}>Status</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-[#454A50] dark:text-[#9E9993] uppercase mb-1">Sort Direction</label>
                        <select name="sort_order" class="w-full px-3 py-2 text-xs rounded-lg border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                            <option value="desc" {{ ($filters['sort_order'] ?? 'desc') === 'desc' ? 'selected' : '' }}>Descending (Newest first)</option>
                            <option value="asc" {{ ($filters['sort_order'] ?? '') === 'asc' ? 'selected' : '' }}>Ascending (Oldest first)</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        {{-- Incident Records Table --}}
        <div class="alerto-card overflow-hidden">
            @if($incidents->isEmpty())
                <div class="p-12 text-center">
                    <div class="w-16 h-16 rounded-full bg-[#F5F3EF] dark:bg-white/5 text-[#77736E] flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="inbox" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-base font-bold">No incident records found</h3>
                    <p class="text-xs text-[#77736E] max-w-sm mx-auto mt-1">
                        There are currently no incidents matching the selected tab and filter criteria.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[#F5F3EF] dark:bg-white/5 border-b border-[#E6E3DE] dark:border-white/10 text-[11px] font-bold uppercase tracking-wider text-[#77736E]">
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
                        <tbody class="divide-y divide-[#E6E3DE] dark:divide-white/10 text-xs">
                            @foreach($incidents as $inc)
                                <tr class="hover:bg-[#FFF0E4]/40 dark:hover:bg-white/5 transition-colors group">
                                    {{-- Reference Number --}}
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <a href="{{ route('admin.incidents.show', $inc->reference_number) }}" 
                                           class="font-mono font-bold text-[#B94F12] dark:text-[#F29A56] hover:text-[#E87524] transition-colors inline-flex items-center gap-1.5">
                                            <span>{{ $inc->reference_number }}</span>
                                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                        </a>
                                        <div class="text-[10px] text-[#77736E] font-mono mt-0.5">
                                            {{ strtoupper(str_replace('_', ' ', $inc->source_channel)) }}
                                        </div>
                                    </td>

                                    {{-- Category & Priority --}}
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold">{{ $inc->category?->name ?? 'Emergency' }}</div>
                                        <div class="mt-1">
                                            @php
                                                $priorityStyles = [
                                                    'critical' => 'bg-[#C83F43]/15 text-[#C83F43] border-[#C83F43]/30',
                                                    'high' => 'bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56] border-[#E87524]/30',
                                                    'medium' => 'bg-[#397BA8]/15 text-[#397BA8] border-[#397BA8]/30',
                                                    'low' => 'bg-black/5 dark:bg-white/10 text-[#454A50] dark:text-[#CFCBC4] border-[#E6E3DE] dark:border-white/15',
                                                ];
                                            @endphp
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase border {{ $priorityStyles[$inc->priority] ?? 'bg-black/5 text-[#454A50]' }}">
                                                @if($inc->priority === 'critical')
                                                    <span class="w-1.5 h-1.5 rounded-full bg-[#C83F43] animate-ping"></span>
                                                @endif
                                                {{ strtoupper($inc->priority) }}
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Location --}}
                                    <td class="py-3.5 px-4">
                                        <div class="font-semibold">{{ $inc->barangay?->name ?? 'Hinoba-an' }}</div>
                                        <div class="text-[11px] text-[#77736E] truncate max-w-xs mt-0.5">
                                            {{ $inc->location?->landmark ?? $inc->location?->street_address ?? 'Coordinates Pinned' }}
                                        </div>
                                    </td>

                                    {{-- Reporter & Phone --}}
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="font-medium">{{ $inc->caller_name }}</div>
                                        <div class="text-[11px] font-mono text-[#77736E] mt-0.5 flex items-center gap-1.5">
                                            <span>{{ $inc->caller_phone }}</span>
                                            @if($inc->contact_verification_status === 'verified')
                                                <span class="px-1.5 py-0.2 rounded-sm text-[9px] font-bold bg-[#27845B]/15 text-[#27845B]" title="Verified Contact">VERIFIED</span>
                                            @elseif($inc->contact_verification_status === 'failed_verification')
                                                <span class="px-1.5 py-0.2 rounded-sm text-[9px] font-bold bg-[#C83F43]/15 text-[#C83F43]" title="Verification Failed">FAILED</span>
                                            @else
                                                <span class="px-1.5 py-0.2 rounded-sm text-[9px] font-bold bg-[#D99024]/15 text-[#B94F12] dark:text-[#F29A56]" title="Unverified Contact">UNVERIFIED</span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Status --}}
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        @php
                                            $statusBadges = [
                                                'pending_review' => ['bg' => 'bg-[#D99024]/15 text-[#B94F12] dark:text-[#F29A56] border-[#D99024]/30', 'label' => 'PENDING REVIEW'],
                                                'accepted' => ['bg' => 'bg-[#397BA8]/15 text-[#397BA8] border-[#397BA8]/30', 'label' => 'ACCEPTED'],
                                                'dispatched' => ['bg' => 'bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56] border-[#E87524]/30', 'label' => 'DISPATCHED'],
                                                'acknowledged' => ['bg' => 'bg-[#E87524]/20 text-[#B94F12] dark:text-[#F29A56] border-[#E87524]/40', 'label' => 'ACKNOWLEDGED'],
                                                'on_the_way' => ['bg' => 'bg-[#D99024]/15 text-[#D99024] border-[#D99024]/30', 'label' => 'EN ROUTE'],
                                                'on_scene' => ['bg' => 'bg-[#27845B]/15 text-[#27845B] border-[#27845B]/30', 'label' => 'ON SCENE'],
                                                'in_progress' => ['bg' => 'bg-[#27845B]/15 text-[#27845B] border-[#27845B]/30', 'label' => 'IN PROGRESS'],
                                                'resolved' => ['bg' => 'bg-[#27845B]/15 text-[#27845B] border-[#27845B]/30', 'label' => 'RESOLVED'],
                                                'closed' => ['bg' => 'bg-black/5 dark:bg-white/10 text-[#454A50] dark:text-[#CFCBC4] border-[#E6E3DE] dark:border-white/15', 'label' => 'CLOSED'],
                                                'rejected' => ['bg' => 'bg-[#C83F43]/15 text-[#C83F43] border-[#C83F43]/30', 'label' => 'REJECTED'],
                                            ];
                                            $badge = $statusBadges[$inc->status] ?? ['bg' => 'bg-black/5 text-[#454A50] border-[#E6E3DE]', 'label' => strtoupper($inc->status)];
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold border {{ $badge['bg'] }}">
                                            {{ $badge['label'] }}
                                        </span>
                                    </td>

                                    {{-- Assigned Unit --}}
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        @if($inc->activeAssignments->isNotEmpty())
                                            @php $assignment = $inc->activeAssignments->first(); @endphp
                                            <div class="font-medium">{{ $assignment->office?->name ?? 'Designated Office' }}</div>
                                            <div class="text-[10px] text-[#77736E]">
                                                {{ $assignment->team?->team_name ?? ($assignment->personnel?->rank_designation ?? 'Assigned Unit') }}
                                            </div>
                                        @else
                                            <span class="text-[11px] text-[#77736E] italic">Unassigned</span>
                                        @endif
                                    </td>

                                    {{-- Submitted Date --}}
                                    <td class="py-3.5 px-4 whitespace-nowrap text-[#454A50] dark:text-[#CFCBC4]">
                                        <div>{{ $inc->submitted_at?->format('M d, Y') }}</div>
                                        <div class="text-[10px] text-[#77736E] font-mono">{{ $inc->submitted_at?->format('H:i') }} ({{ $inc->submitted_at?->diffForHumans() }})</div>
                                    </td>

                                    {{-- Action Button --}}
                                    <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                        <a href="{{ route('admin.incidents.show', $inc->reference_number) }}" 
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $inc->status === 'pending_review' ? 'bg-[#C83F43] text-white hover:bg-[#A63034] shadow-xs' : ($inc->status === 'accepted' ? 'bg-[#E87524] text-white hover:bg-[#B94F12]' : 'bg-[#F5F3EF] dark:bg-white/10 hover:bg-[#E6E3DE] dark:hover:bg-white/20') }}">
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
                    <div class="p-4 border-t border-[#E6E3DE] dark:border-white/10 bg-[#F5F3EF]/60 dark:bg-white/5">
                        {{ $incidents->links() }}
                    </div>
                @endif
            @endif
        </div>

    </div>
</x-layouts.admin>

