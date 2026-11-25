<x-layouts.office>
    <div class="space-y-6">

        {{-- Top Bar & Title --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#173957]/10 text-[#173957] text-[11px] font-bold uppercase tracking-wider mb-1">
                    <i data-lucide="file-text" class="w-3.5 h-3.5 text-[#3187D8]"></i>
                    Agency Archive · {{ $office->code }}
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                    Records &amp; Reports
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Historical incident logs, completed emergency responses, and agency export reports.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('office.records.export', request()->all()) }}" 
                   class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md transition-all flex items-center gap-1.5">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    <span>Export CSV Report</span>
                </a>
            </div>
        </div>

        {{-- Filter Bar --}}
        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
            <form method="GET" action="{{ route('office.records') }}" class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Timeframe</label>
                    <select name="timeframe" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
                        <option value="">All Time</option>
                        <option value="daily" {{ ($filters['timeframe'] ?? '') === 'daily' ? 'selected' : '' }}>Today (Daily)</option>
                        <option value="weekly" {{ ($filters['timeframe'] ?? '') === 'weekly' ? 'selected' : '' }}>Past 7 Days (Weekly)</option>
                        <option value="monthly" {{ ($filters['timeframe'] ?? '') === 'monthly' ? 'selected' : '' }}>Past 30 Days (Monthly)</option>
                        <option value="annual" {{ ($filters['timeframe'] ?? '') === 'annual' ? 'selected' : '' }}>Past Year (Annual)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Category</label>
                    <select name="category_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ ($filters['category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
                        <option value="">All Statuses</option>
                        <option value="resolved" {{ ($filters['status'] ?? '') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 py-2 px-3 bg-[#173957] text-white font-bold text-xs rounded-xl hover:bg-[#1f4b72] transition-colors">
                        Apply Filter
                    </button>
                    @if(!empty(array_filter($filters)))
                        <a href="{{ route('office.records') }}" class="py-2 px-3 bg-slate-100 text-slate-600 font-bold text-xs rounded-xl hover:bg-slate-200 transition-colors">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Reports Table --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-[10px] tracking-wider font-bold">
                        <tr>
                            <th class="py-3 px-4">Reference</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4">Barangay</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Priority</th>
                            <th class="py-3 px-4">Submitted Date</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($records as $rec)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-slate-900">{{ $rec->reference_number }}</td>
                                <td class="py-3 px-4 text-slate-700">{{ $rec->category?->name }}</td>
                                <td class="py-3 px-4 font-bold text-slate-800">{{ $rec->barangay?->name }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                        {{ in_array($rec->status, ['resolved', 'closed']) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $rec->status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 uppercase font-bold text-[10px]">{{ $rec->priority }}</td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-500">
                                    {{ $rec->submitted_at ? $rec->submitted_at->format('M d, Y H:i') : '—' }}
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a href="{{ route('office.incidents.show', $rec->reference_number) }}" class="font-bold text-[#3187D8] hover:underline">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    No records match the selected report criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-layouts.office>
