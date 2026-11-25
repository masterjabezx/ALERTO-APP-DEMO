<x-layouts.office>
    <div class="space-y-6">

        {{-- Top Bar & Title --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#173957]/10 text-[#173957] text-[11px] font-bold uppercase tracking-wider mb-1">
                    <i data-lucide="radio" class="w-3.5 h-3.5 text-[#3187D8]"></i>
                    Agency Triage Console · {{ $office->code }}
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                    Incident Monitoring &amp; Agency Intake
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Emergency incidents routed to or assigned under {{ $office->name }}.
                </p>
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

        {{-- Filter Bar --}}
        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
            <form method="GET" action="{{ route('office.incidents') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Search Incidents</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" 
                           placeholder="Reference, caller, keywords..." 
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Category</label>
                    <select name="category_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ ($filters['category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Barangay</label>
                    <select name="barangay_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                        <option value="">All Barangays</option>
                        @foreach($barangays as $bg)
                            <option value="{{ $bg->id }}" {{ ($filters['barangay_id'] ?? '') == $bg->id ? 'selected' : '' }}>
                                {{ $bg->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
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
                    <button type="submit" class="flex-1 py-2 px-3 bg-[#173957] text-white font-bold text-xs rounded-xl hover:bg-[#1f4b72] transition-colors">
                        Filter
                    </button>
                    @if(!empty(array_filter($filters)))
                        <a href="{{ route('office.incidents') }}" class="py-2 px-3 bg-slate-100 text-slate-600 font-bold text-xs rounded-xl hover:bg-slate-200 transition-colors">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Incidents Monitoring Table --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-[10px] tracking-wider font-bold">
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
                    <tbody class="divide-y divide-slate-100">
                        @forelse($incidents as $inc)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="font-mono font-bold text-slate-900">{{ $inc->reference_number }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $inc->category?->name }}</div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-800">{{ $inc->barangay?->name }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $inc->location?->street_name ?? 'Coordinates on file' }}</div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                        {{ $inc->priority === 'critical' ? 'bg-red-500 text-white' : '' }}
                                        {{ $inc->priority === 'high' ? 'bg-amber-100 text-amber-800' : '' }}
                                        {{ $inc->priority === 'medium' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $inc->priority === 'low' ? 'bg-slate-100 text-slate-700' : '' }}">
                                        {{ ucfirst($inc->priority) }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold
                                        {{ $inc->status === 'pending_review' ? 'bg-amber-100 text-amber-800' : '' }}
                                        {{ $inc->status === 'accepted' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $inc->status === 'dispatched' ? 'bg-purple-100 text-purple-800' : '' }}
                                        {{ in_array($inc->status, ['acknowledged', 'on_the_way', 'on_scene', 'in_progress']) ? 'bg-indigo-100 text-indigo-800' : '' }}
                                        {{ in_array($inc->status, ['resolved', 'closed']) ? 'bg-emerald-100 text-emerald-800' : '' }}
                                        {{ $inc->status === 'rejected' ? 'bg-rose-100 text-rose-800' : '' }}">
                                        {{ str_replace('_', ' ', strtoupper($inc->status)) }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-4">
                                    @php
                                        $primaryAssignment = $inc->activeAssignments->first();
                                    @endphp
                                    @if($primaryAssignment)
                                        <div class="font-bold text-slate-800">
                                            {{ $primaryAssignment->team?->call_sign ?? $primaryAssignment->personnel?->user?->name ?? 'Direct Assignment' }}
                                        </div>
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">Unassigned</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500">
                                    {{ $inc->submitted_at ? $inc->submitted_at->diffForHumans() : '—' }}
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <a href="{{ route('office.incidents.show', $inc->reference_number) }}" 
                                       class="px-3 py-1.5 rounded-lg text-xs font-bold text-[#173957] hover:bg-slate-100 transition-colors">
                                        Inspect
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <i data-lucide="radio" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                    <p class="font-bold text-slate-600">No incidents found in agency monitoring scope.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($incidents->hasPages())
                <div class="p-4 border-t border-slate-200 bg-slate-50">
                    {{ $incidents->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layouts.office>
