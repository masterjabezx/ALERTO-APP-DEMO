<x-layouts.office>
    <div class="space-y-6">

        {{-- Offline Snapshot Notice Banner (Module 5) --}}
        <div x-show="!$store.pwa.isOnline" x-cloak class="p-4 rounded-2xl bg-amber-950/40 border border-amber-600/60 text-slate-200 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <i data-lucide="shield-alert" class="w-5 h-5 text-amber-400 shrink-0"></i>
                <div>
                    <h4 class="font-extrabold text-xs text-white uppercase tracking-wider">Office Portal Offline Snapshot Mode</h4>
                    <p class="text-[11px] text-amber-200 mt-0.5">
                        Displaying cached office data. Incident acceptance, rejection, and unit dispatching are disabled until online connection is restored.
                    </p>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40 whitespace-nowrap">
                Read-Only
            </span>
        </div>

        {{-- Office Coordination Header & Filters --}}
        <div class="bg-white rounded-[24px] p-6 border border-slate-200/80 shadow-[0_4px_20px_-2px_rgba(23,57,87,0.06)] flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-[#173957] tracking-tight">Agency Dispatch Operations</h1>
                <p class="text-xs text-slate-500 mt-1">Hinoba-an Incident Coordination, Dispatch Routing &amp; Agency Monitoring</p>
            </div>

            <div class="flex items-center gap-3">
                <x-search-bar placeholder="Search reference, barangay, or unit..." />
                <x-filter-panel>
                    <x-select label="Incident Status" name="status" :options="['all' => 'All Statuses', 'active' => 'Active Only', 'dispatched' => 'Dispatched', 'resolved' => 'Resolved']" />
                    <x-select label="Priority" name="priority" :options="['all' => 'All Priorities', 'critical' => 'Critical', 'high' => 'High', 'normal' => 'Normal']" />
                </x-filter-panel>
            </div>
        </div>

        {{-- 4 Statistics Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card title="Incoming Unverified" value="4" variant="amber" icon="inbox" description="Awaiting agency review" />
            <x-stat-card title="Active Dispatches" value="9" variant="red" icon="flame" description="Response units on scene" />
            <x-stat-card title="Available Units" value="12" variant="green" icon="users" description="Ready for assignment" />
            <x-stat-card title="24h Resolved" value="31" variant="blue" icon="check-circle" description="Closed successfully" />
        </div>

        {{-- Incident Monitoring Table --}}
        <x-table :headers="['Reference', 'Category', 'Barangay / Location', 'Reported', 'Status', 'Assigned Team', 'Actions']">
            <x-slot:toolbar>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-extrabold text-slate-900">Incident Monitoring Feed</span>
                    <x-badge variant="info" size="sm">Live Updates</x-badge>
                </div>
                <x-button size="sm" variant="secondary" icon="plus">Log Office Intake</x-button>
            </x-slot:toolbar>

            {{-- Mocked Professional Incident Rows --}}
            <tr class="hover:bg-slate-50/80 transition-colors">
                <td class="px-6 py-4 font-mono font-bold text-xs text-[#173957]">INC-HNO-0051</td>
                <td class="px-6 py-4 font-semibold text-slate-900">Medical — Respiratory Distress</td>
                <td class="px-6 py-4 text-slate-600 text-xs">Poblacion 1 (Near Plaza)</td>
                <td class="px-6 py-4 text-slate-500 text-xs">3 mins ago</td>
                <td class="px-6 py-4"><x-badge variant="emergency" size="sm" dot pulse>Dispatched</x-badge></td>
                <td class="px-6 py-4 text-xs font-medium text-slate-700">MDRRMO Alpha 1</td>
                <td class="px-6 py-4">
                    <x-button size="sm" variant="outline">Dispatch</x-button>
                </td>
            </tr>

            <tr class="hover:bg-slate-50/80 transition-colors">
                <td class="px-6 py-4 font-mono font-bold text-xs text-[#173957]">INC-HNO-0050</td>
                <td class="px-6 py-4 font-semibold text-slate-900">Structural Fire (Kitchen Smoke)</td>
                <td class="px-6 py-4 text-slate-600 text-xs">Barangay Bacuyangan</td>
                <td class="px-6 py-4 text-slate-500 text-xs">14 mins ago</td>
                <td class="px-6 py-4"><x-badge variant="warning" size="sm" dot>On Scene</x-badge></td>
                <td class="px-6 py-4 text-xs font-medium text-slate-700">BFP Hinoba-an Engine 1</td>
                <td class="px-6 py-4">
                    <x-button size="sm" variant="outline">Monitor</x-button>
                </td>
            </tr>

            <tr class="hover:bg-slate-50/80 transition-colors">
                <td class="px-6 py-4 font-mono font-bold text-xs text-[#173957]">INC-HNO-0048</td>
                <td class="px-6 py-4 font-semibold text-slate-900">Vehicular Collision</td>
                <td class="px-6 py-4 text-slate-600 text-xs">Sitio Asia Highway</td>
                <td class="px-6 py-4 text-slate-500 text-xs">45 mins ago</td>
                <td class="px-6 py-4"><x-badge variant="success" size="sm">Resolved</x-badge></td>
                <td class="px-6 py-4 text-xs font-medium text-slate-700">PNP &amp; Rescue Team</td>
                <td class="px-6 py-4">
                    <x-button size="sm" variant="ghost">Report</x-button>
                </td>
            </tr>
        </x-table>

        {{-- Office GIS Map Panel --}}
        <div class="space-y-3">
            <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-700">Office Sector GIS View</h3>
            <x-map-container height="h-80" title="Hinoba-an Sector Coordination Grid" />
        </div>

    </div>
</x-layouts.office>
