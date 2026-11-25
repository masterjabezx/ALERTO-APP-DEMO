<x-layouts.admin :title="'Reports & Analytics — ALERTO EOC'">
    <div class="space-y-6" x-data="{ charts: {{ Js::from($charts) }} }" x-init="$nextTick(() => window.AlertoEoc?.charts?.renderDashboardCharts(charts))">
        {{-- Header --}}
        <x-portal.page-header
            class="print:hidden"
            badge="Operational Intelligence · Hinoba-an LDRRMO"
            title="Reports &amp; Analytics"
            subtitle="Multi-dimensional emergency trends, barangay comparisons, SLA response timings, and CSV/PDF reporting exports."
        >
            <x-slot:actions>
                <a href="{{ route('admin.reports.export', $filters) }}"
                   class="alerto-btn-press inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#27845B] hover:bg-[#1f6b49] text-white text-xs font-bold shadow-xs">
                    <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                    <span>Export CSV / Excel</span>
                </a>
                <button type="button" onclick="window.print()"
                        class="alerto-btn-press inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white text-xs font-bold shadow-xs">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    <span>Print / Save PDF</span>
                </button>
            </x-slot:actions>
        </x-portal.page-header>

        {{-- Filters --}}
        <div class="alerto-card p-4 print:hidden">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 text-xs">
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                <select name="category_id" class="px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                    <option value="">All Categories</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" {{ ($filters['category_id'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
                <select name="barangay_id" class="px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                    <option value="">All Barangays</option>
                    @foreach($barangays as $b)
                        <option value="{{ $b->id }}" {{ ($filters['barangay_id'] ?? '') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                    <option value="all">All Statuses</option>
                    @foreach(['submitted', 'pending_review', 'accepted', 'dispatched', 'in_progress', 'resolved', 'closed', 'rejected'] as $st)
                        <option value="{{ $st }}" {{ ($filters['status'] ?? '') === $st ? 'selected' : '' }}>{{ strtoupper(str_replace('_', ' ', $st)) }}</option>
                    @endforeach
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="alerto-btn-press flex-1 px-4 py-2 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white font-bold">Filter</button>
                    <a href="{{ route('admin.reports.index') }}" class="px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 font-bold hover:bg-[#F5F3EF] dark:hover:bg-white/10">Reset</a>
                </div>
            </form>
        </div>

        {{-- Real Database KPI Performance Strip --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="alerto-card p-4">
                <span class="text-[10px] font-bold uppercase text-[#77736E] block">Total Recorded Incidents</span>
                <p class="text-2xl font-black font-mono mt-1">{{ number_format($stats['total']) }}</p>
            </div>
            <div class="alerto-card p-4 border-[#E87524]/30">
                <span class="text-[10px] font-bold uppercase text-[#77736E] block">Avg Triage Acceptance</span>
                <p class="text-2xl font-black font-mono text-[#E87524] mt-1">{{ $charts['response_performance']['avg_accept_minutes'] }} min</p>
                <span class="text-[10px] text-[#77736E]">{{ $charts['response_performance']['sampled_accepts'] }} reviewed incidents</span>
            </div>
            <div class="alerto-card p-4 border-[#27845B]/30">
                <span class="text-[10px] font-bold uppercase text-[#77736E] block">Avg Full Resolution</span>
                <p class="text-2xl font-black font-mono text-[#27845B] mt-1">{{ $charts['response_performance']['avg_resolve_minutes'] }} min</p>
                <span class="text-[10px] text-[#77736E]">{{ $charts['response_performance']['sampled_resolutions'] }} resolved cases</span>
            </div>
            <div class="alerto-card p-4 border-[#D99024]/30">
                <span class="text-[10px] font-bold uppercase text-[#77736E] block">Pending Review Queue</span>
                <p class="text-2xl font-black font-mono text-[#D99024] mt-1">{{ number_format($stats['pending_review']) }}</p>
            </div>
        </div>

        {{-- Animated Charts Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <div class="alerto-card p-4 space-y-2">
                <h3 class="text-xs font-bold uppercase text-[#77736E]">Monthly Incident Trends</h3>
                <div class="h-56"><canvas id="chart-monthly-trends"></canvas></div>
            </div>
            <div class="alerto-card p-4 space-y-2">
                <h3 class="text-xs font-bold uppercase text-[#77736E]">Category Distribution</h3>
                <div class="h-56"><canvas id="chart-by-category"></canvas></div>
            </div>
            <div class="alerto-card p-4 space-y-2">
                <h3 class="text-xs font-bold uppercase text-[#77736E]">Barangay Comparison</h3>
                <div class="h-56"><canvas id="chart-by-barangay"></canvas></div>
            </div>
            <div class="alerto-card p-4 space-y-2">
                <h3 class="text-xs font-bold uppercase text-[#77736E]">Status Distribution</h3>
                <div class="h-56"><canvas id="chart-by-status"></canvas></div>
            </div>
            <div class="alerto-card p-4 space-y-2">
                <h3 class="text-xs font-bold uppercase text-[#77736E]">Office Performance</h3>
                <div class="h-56"><canvas id="chart-office-activity"></canvas></div>
            </div>
            <div class="alerto-card p-4 space-y-2">
                <h3 class="text-xs font-bold uppercase text-[#77736E]">Personnel Activity</h3>
                <div class="h-56"><canvas id="chart-personnel-activity"></canvas></div>
            </div>
        </div>

        {{-- Detailed Records Table --}}
        <div class="alerto-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-[#E6E3DE] dark:border-white/10 text-[10px] font-bold uppercase tracking-wider text-[#77736E] bg-[#F5F3EF] dark:bg-white/5">
                            <th class="py-3 px-4">Reference</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4">Barangay</th>
                            <th class="py-3 px-4">Priority</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Office Assigned</th>
                            <th class="py-3 px-4">Submitted At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E6E3DE] dark:divide-white/10">
                        @forelse($incidents as $inc)
                            <tr class="hover:bg-[#FFF0E4]/40 dark:hover:bg-white/5 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-[#B94F12] dark:text-[#F29A56]">
                                    <a href="{{ route('admin.incidents.show', $inc->reference_number) }}" class="hover:underline">{{ $inc->reference_number }}</a>
                                </td>
                                <td class="py-3 px-4">{{ $inc->category?->name ?? 'Emergency' }}</td>
                                <td class="py-3 px-4">{{ $inc->barangay?->name ?? 'Hinoba-an' }}</td>
                                <td class="py-3 px-4 font-mono uppercase">{{ $inc->priority }}</td>
                                <td class="py-3 px-4 font-mono uppercase">{{ str_replace('_', ' ', $inc->status) }}</td>
                                <td class="py-3 px-4">{{ $inc->activeAssignments->first()?->office?->name ?? 'Unassigned' }}</td>
                                <td class="py-3 px-4 font-mono text-[#77736E]">{{ $inc->submitted_at?->format('M d, Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-[#77736E]">No incident records found for selected report parameters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($incidents->hasPages())
                <div class="p-4 border-t border-[#E6E3DE] dark:border-white/10 print:hidden">
                    {{ $incidents->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
