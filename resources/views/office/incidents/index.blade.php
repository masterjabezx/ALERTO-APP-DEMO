<x-layouts.office :title="'Incident Monitoring — ALERTO Office'">
    <div class="space-y-6">

        {{-- Top Bar & Title --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#E87524]/12 border border-[#E87524]/25 text-[#B94F12] dark:text-[#F29A56] text-[11px] font-bold uppercase tracking-wider mb-1">
                    <i data-lucide="radio" class="w-3.5 h-3.5 text-[#E87524]"></i>
                    Agency Triage Console · {{ $office->code }}
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-[var(--text-primary)]">
                    Incident Monitoring &amp; Agency Intake
                </h1>
                <p class="text-xs sm:text-sm text-[var(--text-secondary)] mt-1">
                    Emergency incidents routed to or assigned under {{ $office->name }}.
                </p>
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="p-4 rounded-xl bg-[#27845B]/12 border border-[#27845B]/30 text-[#1B6644] dark:text-[#5BD19A] text-xs font-medium flex items-center gap-3">
                <i data-lucide="check-circle" class="w-4 h-4 text-[#27845B] dark:text-[#5BD19A] shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 rounded-xl bg-[#C83F43]/12 border border-[#C83F43]/30 text-[#9F292D] dark:text-[#F27D80] text-xs font-medium flex items-center gap-3">
                <i data-lucide="alert-octagon" class="w-4 h-4 text-[#C83F43] dark:text-[#F27D80] shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Filter Bar --}}
        <div class="alerto-card p-4">
            <form method="GET" action="{{ route('office.incidents') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-[var(--text-muted)] mb-1">Search Incidents</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" 
                           placeholder="Reference, caller, keywords..." 
                           class="w-full px-3 py-2 text-xs rounded-xl bg-[var(--surface-panel)] border border-[var(--border-subtle)] text-[var(--text-primary)] placeholder:text-[var(--text-muted)] outline-hidden focus-visible:border-[#E87524] focus-visible:ring-2 focus-visible:ring-[#E87524]/25">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-[var(--text-muted)] mb-1">Category</label>
                    <select name="category_id" class="w-full px-3 py-2 text-xs rounded-xl bg-[var(--surface-panel)] border border-[var(--border-subtle)] text-[var(--text-primary)] outline-hidden focus-visible:border-[#E87524] focus-visible:ring-2 focus-visible:ring-[#E87524]/25">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ ($filters['category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-[var(--text-muted)] mb-1">Barangay</label>
                    <select name="barangay_id" class="w-full px-3 py-2 text-xs rounded-xl bg-[var(--surface-panel)] border border-[var(--border-subtle)] text-[var(--text-primary)] outline-hidden focus-visible:border-[#E87524] focus-visible:ring-2 focus-visible:ring-[#E87524]/25">
                        <option value="">All Barangays</option>
                        @foreach($barangays as $bg)
                            <option value="{{ $bg->id }}" {{ ($filters['barangay_id'] ?? '') == $bg->id ? 'selected' : '' }}>
                                {{ $bg->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-[var(--text-muted)] mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 text-xs rounded-xl bg-[var(--surface-panel)] border border-[var(--border-subtle)] text-[var(--text-primary)] outline-hidden focus-visible:border-[#E87524] focus-visible:ring-2 focus-visible:ring-[#E87524]/25">
                        <option value="">All Statuses</option>
                        <option value="pending_review" {{ ($filters['status'] ?? '') === 'pending_review' ? 'selected' : '' }}>Pending Review</option>
                        <option value="accepted" {{ ($filters['status'] ?? '') === 'accepted' ? 'selected' : '' }}>Accepted</option>
                        <option value="dispatched" {{ ($filters['status'] ?? '') === 'dispatched' ? 'selected' : '' }}>Dispatched</option>
                        <option value="on_scene" {{ ($filters['status'] ?? '') === 'on_scene' ? 'selected' : '' }}>On Scene</option>
                        <option value="resolved" {{ ($filters['status'] ?? '') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 py-2 px-3 bg-[#E87524] hover:bg-[#D16419] text-white font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer">
                        Filter
                    </button>
                    @if(!empty(array_filter($filters)))
                        <a href="{{ route('office.incidents') }}" class="py-2 px-3 bg-[var(--surface-panel)] border border-[var(--border-subtle)] text-[var(--text-secondary)] font-bold text-xs rounded-xl hover:bg-[var(--surface-hover)] transition-colors">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Incidents Monitoring Table --}}
        <div class="alerto-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[var(--surface-panel)] border-b border-[var(--border-subtle)] text-[var(--text-muted)] uppercase text-[10px] tracking-wider font-bold">
                        <tr>
                            <th class="py-3 px-4">Reference &amp; Category</th>
                            <th class="py-3 px-4">Barangay &amp; Location</th>
                            <th class="py-3 px-4">Priority</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Assigned Unit</th>
                            <th class="py-3 px-4">Submitted</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border-subtle)]">
                        @forelse($incidents as $inc)
                            <tr class="hover:bg-[var(--surface-hover)]/60 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="font-mono font-bold text-[var(--text-primary)]">{{ $inc->reference_number }}</div>
                                    <div class="text-[11px] text-[var(--text-muted)]">{{ $inc->category?->name }}</div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-[var(--text-primary)]">{{ $inc->barangay?->name }}</div>
                                    <div class="text-[11px] text-[var(--text-muted)]">{{ $inc->location?->street_address ?? $inc->location?->street_name ?? 'Coordinates on file' }}</div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                        {{ $inc->priority === 'critical' ? 'bg-[#C83F43] text-white' : '' }}
                                        {{ $inc->priority === 'high' ? 'bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56]' : '' }}
                                        {{ $inc->priority === 'medium' ? 'bg-[#397BA8]/15 text-[#397BA8]' : '' }}
                                        {{ $inc->priority === 'low' ? 'bg-black/5 dark:bg-white/10 text-[#77736E]' : '' }}">
                                        {{ ucfirst($inc->priority) }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold
                                        {{ $inc->status === 'pending_review' ? 'bg-[#D99024]/15 text-[#B94F12] dark:text-[#F29A56]' : '' }}
                                        {{ $inc->status === 'accepted' ? 'bg-[#397BA8]/15 text-[#397BA8]' : '' }}
                                        {{ $inc->status === 'dispatched' ? 'bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56]' : '' }}
                                        {{ in_array($inc->status, ['acknowledged', 'on_the_way', 'on_scene', 'in_progress']) ? 'bg-[#E87524]/20 text-[#B94F12] dark:text-[#F29A56]' : '' }}
                                        {{ in_array($inc->status, ['resolved', 'closed']) ? 'bg-[#27845B]/15 text-[#27845B]' : '' }}
                                        {{ $inc->status === 'rejected' ? 'bg-[#C83F43]/15 text-[#C83F43]' : '' }}">
                                        {{ str_replace('_', ' ', strtoupper($inc->status)) }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-4">
                                    @php
                                        $primaryAssignment = $inc->activeAssignments->first();
                                    @endphp
                                    @if($primaryAssignment)
                                        <div class="font-bold">
                                            {{ $primaryAssignment->team?->call_sign ?? $primaryAssignment->personnel?->user?->name ?? 'Direct Assignment' }}
                                        </div>
                                    @else
                                        <span class="text-[#77736E] italic text-[11px]">Unassigned</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 font-mono text-[11px] text-[#77736E]">
                                    {{ $inc->submitted_at ? $inc->submitted_at->diffForHumans() : '—' }}
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <a href="{{ route('office.incidents.show', $inc->reference_number) }}"
                                       class="px-3 py-1.5 rounded-lg text-xs font-bold text-[#B94F12] dark:text-[#F29A56] hover:bg-[#E87524]/15 transition-colors">
                                        Inspect
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-[#77736E]">
                                    <i data-lucide="radio" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                    <p class="font-bold">No incidents found in agency monitoring scope.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($incidents->hasPages())
                <div class="p-4 border-t border-[#E6E3DE] dark:border-white/10 bg-[#F5F3EF]/60 dark:bg-white/5">
                    {{ $incidents->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layouts.office>
