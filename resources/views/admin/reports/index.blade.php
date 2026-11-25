<x-layouts.admin :title="'Reports & Analytics — ALERTO EOC'">
    <div class="space-y-6" x-data="{ charts: {{ Js::from($charts) }} }" x-init="$nextTick(() => window.AlertoEoc?.charts?.renderDashboardCharts(charts))">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 print:hidden">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#3187D8]/15 text-[#34B9D4] text-[11px] font-bold uppercase tracking-wider mb-1">
                    <i data-lucide="bar-chart-3" class="w-3.5 h-3.5"></i>
                    Operational Intelligence · Hinoba-an LDRRMO
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Reports &amp; Analytics</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1">
                    Multi-dimensional emergency trends, barangay comparisons, SLA response timings, and CSV/PDF reporting exports.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.reports.export', $filters) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#19865B] hover:bg-[#146e4b] text-white text-xs font-bold shadow-xs">
                    <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                    <span>Export CSV / Excel</span>
                </a>
                <button type="button" onclick="window.print()"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#3187D8] hover:bg-[#2770b6] text-white text-xs font-bold shadow-xs">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    <span>Print / Save PDF</span>
                </button>
            </div>
        </div>

        {{-- Filters --}}
        <div class="eoc-card p-4 print:hidden">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 text-xs">
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="px-3 py-2 rounded-xl border">
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="px-3 py-2 rounded-xl border">
                <select name="category_id" class="px-3 py-2 rounded-xl border">
                    <option value="">All Categories</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" {{ ($filters['category_id'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
                <select name="barangay_id" class="px-3 py-2 rounded-xl border">
                    <option value="">All Barangays</option>
                    @foreach($barangays as $b)
                        <option value="{{ $b->id }}" {{ ($filters['barangay_id'] ?? '') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="px-3 py-2 rounded-xl border">
                    <option value="all">All Statuses</option>
                    @foreach(['submitted', 'pending_review', 'accepted', 'dispatched', 'in_progress', 'resolved', 'closed', 'rejected'] as $st)
                        <option value="{{ $st }}" {{ ($filters['status'] ?? '') === $st ? 'selected' : '' }}>{{ strtoupper(str_replace('_', ' ', $st)) }}</option>
                    @endforeach
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 rounded-xl bg-[#3187D8] text-white font-bold">Filter</button>
                    <a href="{{ route('admin.reports.index') }}" class="px-3 py-2 rounded-xl border border-slate-400/30 font-bold">Reset</a>
                </div>
            </form>
        </div>

        {{-- Real Database KPI Performance Strip --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="eoc-card p-4">
                <span class="text-[10px] font-bold uppercase text-slate-400 block">Total Recorded Incidents</span>
                <p class="text-2xl font-black font-mono mt-1">{{ number_format($stats['total']) }}</p>
            </div>
            <div class="eoc-card p-4">
                <span class="text-[10px] font-bold uppercase text-slate-400 block">Avg Triage Acceptance</span>
                <p class="text-2xl font-black font-mono text-[#34B9D4] mt-1">{{ $charts['response_performance']['avg_accept_minutes'] }} min</p>
                <span class="text-[10px] text-slate-400">{{ $charts['response_performance']['sampled_accepts'] }} reviewed incidents</span>
            </div>
            <div class="eoc-card p-4">
                <span class="text-[10px] font-bold uppercase text-slate-400 block">Avg Full Resolution</span>
                <p class="text-2xl font-black font-mono text-[#19865B] mt-1">{{ $charts['response_performance']['avg_resolve_minutes'] }} min</p>
                <span class="text-[10px] text-slate-400">{{ $charts['response_performance']['sampled_resolutions'] }} resolved cases</span>
            </div>
            <div class="eoc-card p-4">
                <span class="text-[10px] font-bold uppercase text-slate-400 block">Pending Review Queue</span>
                <p class="text-2xl font-black font-mono text-[#F0A82E] mt-1">{{ number_format($stats['pending_review']) }}</p>
            </div>
        </div>

        {{-- Animated Charts Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <div class="eoc-card p-4 space-y-2">
                <h3 class="text-xs font-bold uppercase text-slate-400">Monthly Incident Trends</h3>
                <div class="h-56"><canvas id="chart-monthly-trends"></canvas></div>
            </div>
            <div class="eoc-card p-4 space-y-2">
                <h3 class="text-xs font-bold uppercase text-slate-400">Category Distribution</h3>
                <div class="h-56"><canvas id="chart-by-category"></canvas></div>
            </div>
            <div class="eoc-card p-4 space-y-2">
                <h3 class="text-xs font-bold uppercase text-slate-400">Barangay Comparison</h3>
                <div class="h-56"><canvas id="chart-by-barangay"></canvas></div>
            </div>
            <div class="eoc-card p-4 space-y-2">
                <h3 class="text-xs font-bold uppercase text-slate-400">Status Distribution</h3>
                <div class="h-56"><canvas id="chart-by-status"></canvas></div>
            </div>
            <div class="eoc-card p-4 space-y-2">
                <h3 class="text-xs font-bold uppercase text-slate-400">Office Performance</h3>
                <div class="h-56"><canvas id="chart-office-activity"></canvas></div>
            </div>
            <div class="eoc-card p-4 space-y-2">
                <h3 class="text-xs font-bold uppercase text-slate-400">Personnel Activity</h3>
                <div class="h-56"><canvas id="chart-personnel-activity"></canvas></div>
            </div>
        </div>

        {{-- Detailed Records Table --}}
        <div class="eoc-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-400/20 text-[10px] font-bold uppercase tracking-wider text-slate-400 bg-black/15">
                            <th class="py-3 px-4">Reference</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4">Barangay</th>
                            <th class="py-3 px-4">Priority</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Office Assigned</th>
                            <th class="py-3 px-4">Submitted At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-400/15">
                        @forelse($incidents as $inc)
                            <tr>
                                <td class="py-3 px-4 font-mono font-bold text-[#34B9D4]">
                                    <a href="{{ route('admin.incidents.show', $inc->reference_number) }}" class="hover:underline">{{ $inc->reference_number }}</a>
                                </td>
                                <td class="py-3 px-4">{{ $inc->category?->name ?? 'Emergency' }}</td>
                                <td class="py-3 px-4">{{ $inc->barangay?->name ?? 'Hinoba-an' }}</td>
                                <td class="py-3 px-4 font-mono uppercase">{{ $inc->priority }}</td>
                                <td class="py-3 px-4 font-mono uppercase">{{ str_replace('_', ' ', $inc->status) }}</td>
                                <td class="py-3 px-4">{{ $inc->activeAssignments->first()?->office?->name ?? 'Unassigned' }}</td>
                                <td class="py-3 px-4 font-mono text-slate-400">{{ $inc->submitted_at?->format('M d, Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">No incident records found for selected report parameters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($incidents->hasPages())
                <div class="p-4 border-t border-slate-400/15 print:hidden">
                    {{ $incidents->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>

