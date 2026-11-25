<x-layouts.office :title="'Records & Reports — ' . $office->code">
    <div class="space-y-6">

        {{-- Top Bar & Title --}}
        <x-portal.page-header
            :badge="'Agency Archive · ' . $office->code"
            title="Records &amp; Reports"
            subtitle="Historical incident logs, completed emergency responses, and agency export reports."
        >
            <x-slot:actions>
                <a href="{{ route('office.records.export', request()->all()) }}"
                   class="alerto-btn-press px-4 py-2.5 rounded-xl bg-[#27845B] hover:bg-[#1f6b49] text-white font-bold text-xs shadow-md transition-all flex items-center gap-1.5">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    <span>Export CSV Report</span>
                </a>
            </x-slot:actions>
        </x-portal.page-header>

        {{-- Filter Bar --}}
        <div class="alerto-card p-4">
            <form method="GET" action="{{ route('office.records') }}" class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-[#77736E] mb-1">Timeframe</label>
                    <select name="timeframe" class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                        <option value="">All Time</option>
                        <option value="daily" {{ ($filters['timeframe'] ?? '') === 'daily' ? 'selected' : '' }}>Today (Daily)</option>
                        <option value="weekly" {{ ($filters['timeframe'] ?? '') === 'weekly' ? 'selected' : '' }}>Past 7 Days (Weekly)</option>
                        <option value="monthly" {{ ($filters['timeframe'] ?? '') === 'monthly' ? 'selected' : '' }}>Past 30 Days (Monthly)</option>
                        <option value="annual" {{ ($filters['timeframe'] ?? '') === 'annual' ? 'selected' : '' }}>Past Year (Annual)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-[#77736E] mb-1">Category</label>
                    <select name="category_id" class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ ($filters['category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-[#77736E] mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                        <option value="">All Statuses</option>
                        <option value="resolved" {{ ($filters['status'] ?? '') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="alerto-btn-press flex-1 py-2 px-3 bg-[#E87524] hover:bg-[#B94F12] text-white font-bold text-xs rounded-xl transition-colors">
                        Apply Filter
                    </button>
                    @if(!empty(array_filter($filters)))
                        <a href="{{ route('office.records') }}" class="py-2 px-3 bg-[#F5F3EF] dark:bg-white/10 text-[#77736E] font-bold text-xs rounded-xl hover:bg-[#E6E3DE] dark:hover:bg-white/20 transition-colors">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Reports Table --}}
        <div class="alerto-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F5F3EF] dark:bg-white/5 border-b border-[#E6E3DE] dark:border-white/10 text-[#77736E] uppercase text-[10px] tracking-wider font-bold">
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
                    <tbody class="divide-y divide-[#E6E3DE] dark:divide-white/10">
                        @forelse($records as $rec)
                            <tr class="hover:bg-[#FFF0E4]/40 dark:hover:bg-white/5 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-[#B94F12] dark:text-[#F29A56]">{{ $rec->reference_number }}</td>
                                <td class="py-3 px-4">{{ $rec->category?->name }}</td>
                                <td class="py-3 px-4 font-bold">{{ $rec->barangay?->name }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                        {{ in_array($rec->status, ['resolved', 'closed']) ? 'bg-[#27845B]/15 text-[#27845B]' : 'bg-black/5 dark:bg-white/10 text-[#77736E]' }}">
                                        {{ $rec->status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 uppercase font-bold text-[10px]">{{ $rec->priority }}</td>
                                <td class="py-3 px-4 font-mono text-[11px] text-[#77736E]">
                                    {{ $rec->submitted_at ? $rec->submitted_at->format('M d, Y H:i') : '—' }}
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a href="{{ route('office.incidents.show', $rec->reference_number) }}" class="font-bold text-[#B94F12] dark:text-[#F29A56] hover:underline">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-[#77736E]">
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
