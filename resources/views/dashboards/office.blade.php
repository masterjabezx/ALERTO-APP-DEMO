<x-layouts.office>
    <div class="space-y-6">

        {{-- Offline Snapshot Notice Banner (Module 5) --}}
        <div x-show="!$store.pwa.isOnline" x-cloak class="p-4 rounded-2xl bg-[#D99024]/15 border border-[#D99024]/45 text-[var(--text-primary)] flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <i data-lucide="shield-alert" class="w-5 h-5 text-[#D99024] shrink-0"></i>
                <div>
                    <h4 class="font-extrabold text-xs uppercase tracking-wider text-[var(--text-primary)]">Office Portal Offline Snapshot Mode</h4>
                    <p class="text-[11px] text-[var(--text-secondary)] mt-0.5">
                        Displaying cached office data. Incident acceptance, rejection, and unit dispatching are disabled until online connection is restored.
                    </p>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-[#D99024]/20 text-[#9A5D07] dark:text-[#F6BA53] border border-[#D99024]/40 whitespace-nowrap">
                Read-Only
            </span>
        </div>

        {{-- Office Coordination Header & Filters --}}
        <div class="alerto-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-[var(--text-primary)] tracking-tight">Agency Dispatch Operations</h1>
                <p class="text-xs text-[var(--text-secondary)] mt-1">Hinoba-an Incident Coordination, Dispatch Routing &amp; Agency Monitoring</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
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
            <x-stat-card title="24h Resolved" value="31" variant="orange" icon="check-circle" description="Closed successfully" />
        </div>

        {{-- Incident Monitoring Table --}}
        <x-table :headers="['Reference', 'Category', 'Barangay / Location', 'Reported', 'Status', 'Assigned Team', 'Actions']">
            <x-slot:toolbar>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-extrabold text-[var(--text-primary)]">Incident Monitoring Feed</span>
                    <x-badge variant="info" size="sm">Live Updates</x-badge>
                </div>
                <x-button size="sm" variant="secondary" icon="plus">Log Office Intake</x-button>
            </x-slot:toolbar>

            {{-- Sample Operational Incident Rows --}}
            <tr class="hover:bg-[var(--surface-hover)]/60 transition-colors">
                <td class="px-6 py-4 font-mono font-bold text-xs text-[#B94F12] dark:text-[#F29A56]">INC-HNO-0051</td>
                <td class="px-6 py-4 font-semibold text-[var(--text-primary)]">Medical — Respiratory Distress</td>
                <td class="px-6 py-4 text-[var(--text-secondary)] text-xs">Poblacion 1 (Near Plaza)</td>
                <td class="px-6 py-4 text-[var(--text-muted)] text-xs">3 mins ago</td>
                <td class="px-6 py-4"><x-badge variant="emergency" size="sm" dot pulse>Dispatched</x-badge></td>
                <td class="px-6 py-4 text-xs font-medium text-[var(--text-secondary)]">MDRRMO Alpha 1</td>
                <td class="px-6 py-4">
                    <x-button size="sm" variant="outline">Dispatch</x-button>
                </td>
            </tr>

            <tr class="hover:bg-[var(--surface-hover)]/60 transition-colors">
                <td class="px-6 py-4 font-mono font-bold text-xs text-[#B94F12] dark:text-[#F29A56]">INC-HNO-0050</td>
                <td class="px-6 py-4 font-semibold text-[var(--text-primary)]">Structural Fire (Kitchen Smoke)</td>
                <td class="px-6 py-4 text-[var(--text-secondary)] text-xs">Barangay Bacuyangan</td>
                <td class="px-6 py-4 text-[var(--text-muted)] text-xs">14 mins ago</td>
                <td class="px-6 py-4"><x-badge variant="warning" size="sm" dot>On Scene</x-badge></td>
                <td class="px-6 py-4 text-xs font-medium text-[var(--text-secondary)]">BFP Hinoba-an Engine 1</td>
                <td class="px-6 py-4">
                    <x-button size="sm" variant="outline">Monitor</x-button>
                </td>
            </tr>

            <tr class="hover:bg-[var(--surface-hover)]/60 transition-colors">
                <td class="px-6 py-4 font-mono font-bold text-xs text-[#B94F12] dark:text-[#F29A56]">INC-HNO-0048</td>
                <td class="px-6 py-4 font-semibold text-[var(--text-primary)]">Vehicular Collision</td>
                <td class="px-6 py-4 text-[var(--text-secondary)] text-xs">Sitio Asia Highway</td>
                <td class="px-6 py-4 text-[var(--text-muted)] text-xs">45 mins ago</td>
                <td class="px-6 py-4"><x-badge variant="success" size="sm">Resolved</x-badge></td>
                <td class="px-6 py-4 text-xs font-medium text-[var(--text-secondary)]">PNP &amp; Rescue Team</td>
                <td class="px-6 py-4">
                    <x-button size="sm" variant="ghost">Report</x-button>
                </td>
            </tr>
        </x-table>

        {{-- Office GIS Map Panel --}}
        <div class="space-y-3">
            <h3 class="text-sm font-extrabold uppercase tracking-wider text-[var(--text-secondary)]">Office Sector GIS View</h3>
            <x-map-container height="h-80" title="Hinoba-an Sector Coordination Grid" />
        </div>

    </div>
</x-layouts.office>
