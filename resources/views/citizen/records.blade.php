<x-layouts.citizen>
    <div class="space-y-6">

        {{-- Header & Hotline Shortcut --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-[var(--text-primary)] tracking-tight">Your Incident Records</h1>
                <p class="text-xs text-[var(--text-secondary)] mt-0.5">Track the live progress and response timeline of your submitted reports.</p>
            </div>

            @if(!empty($hotline))
                <a href="tel:{{ preg_replace('/[^0-9]/', '', $hotline) }}" 
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#C83F43]/12 text-[#9F292D] dark:text-[#F27D80] border border-[#C83F43]/30 text-xs font-bold hover:bg-[#C83F43]/20 transition-all">
                    <i data-lucide="phone" class="w-4 h-4"></i>
                    <span>Hotline: {{ $hotline }}</span>
                </a>
            @endif
        </div>

        {{-- Filter Tabs --}}
        <div class="flex items-center gap-1.5 overflow-x-auto alerto-scrollbar pb-1">
            @php
                $filters = [
                    'all' => 'All',
                    'pending' => 'Pending Review',
                    'accepted' => 'Accepted',
                    'dispatched' => 'Dispatched',
                    'in_progress' => 'In Progress',
                    'resolved' => 'Resolved',
                    'rejected' => 'Rejected',
                ];
            @endphp

            @foreach($filters as $key => $label)
                <a href="{{ route('citizen.records', ['status' => $key]) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all {{ $currentFilter === $key ? 'bg-[#E87524] text-white shadow-xs' : 'bg-[var(--surface-card)] border border-[var(--border-subtle)] text-[var(--text-secondary)] hover:border-[#E87524]/45' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Pending Offline Reports on Device (Module 3-B) --}}
        <div x-data="{
                offlineDrafts: [],
                async loadDrafts() {
                    if (window.AlertoPwa && window.AlertoPwa.storage) {
                        const all = await window.AlertoPwa.storage.getDraftIncidents({{ auth()->id() ?? 'null' }});
                        this.offlineDrafts = all.filter(d => d.status !== 'submitted');
                    }
                }
            }"
            x-init="loadDrafts(); window.addEventListener('alerto:sync-complete', () => loadDrafts())"
            x-show="offlineDrafts.length > 0"
            x-cloak
            class="p-4 sm:p-5 rounded-2xl bg-[#17191C] border border-[#D99024]/50 text-[#F5F3EF] space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#F6BA53] animate-pulse"></span>
                    <h3 class="font-extrabold text-sm text-white">Pending Reports Saved on this Device (<span x-text="offlineDrafts.length"></span>)</h3>
                </div>
                <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-sm bg-[#D99024]/20 text-[#F6BA53] border border-[#D99024]/40">Not Yet Received</span>
            </div>
            <p class="text-xs text-[#F6BA53] leading-relaxed">
                The Command Center has not received these reports yet. They will automatically be submitted when your internet connection is restored.
            </p>
            <div class="space-y-2">
                <template x-for="draft in offlineDrafts" :key="draft.client_uuid">
                    <div class="p-3 rounded-xl bg-[#292D32] border border-white/10 flex items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-white" x-text="draft.category_name"></span>
                                <span class="text-[10px] text-[#A39E98]" x-text="new Date(draft.created_at).toLocaleTimeString()"></span>
                            </div>
                            <p class="text-[11px] text-[#D1CEC9] mt-0.5 line-clamp-1" x-text="draft.description || 'Emergency assistance requested.'"></p>
                        </div>
                        <span class="px-2 py-1 rounded-lg text-[10px] font-bold bg-[#D99024]/15 text-[#F6BA53] border border-[#D99024]/30 whitespace-nowrap">
                            Waiting for Connection
                        </span>
                    </div>
                </template>
            </div>
        </div>

        {{-- Cached records offline indicator --}}
        <div x-show="!$store.pwa.isOnline" x-cloak class="p-3 rounded-xl bg-[var(--surface-panel)] border border-[var(--border-subtle)] text-xs text-[var(--text-secondary)] flex items-center justify-between">
            <span>Viewing cached incident history. Statuses will refresh when connected.</span>
            <span class="font-bold text-[#B94F12] dark:text-[#F29A56]">Offline Cache</span>
        </div>

        {{-- Incidents List --}}
        @if($incidents->count() > 0)
            <div class="space-y-3.5">
                @foreach($incidents as $inc)
                    <x-incident-card 
                        :category="$inc->category?->name ?? 'Awaiting Call Classification'"
                        :reference="$inc->reference_number"
                        :location="$inc->barangay?->name ?? 'GPS Coordinates Captured'"
                        :status="$inc->status"
                        :priority="$inc->priority ?? 'unassigned'"
                        :reportedAt="$inc->submitted_at?->diffForHumans()"
                    >
                        <span class="text-xs text-[var(--text-muted)]">
                            {{ ucfirst(str_replace('_', ' ', $inc->status)) }}
                        </span>

                        <x-button size="sm" variant="outline" href="{{ route('citizen.records.show', $inc->reference_number) }}" icon="chevron-right" iconPosition="right">
                            View Details
                        </x-button>
                    </x-incident-card>
                @endforeach

                {{-- Pagination --}}
                <div class="pt-3">
                    {{ $incidents->links() }}
                </div>
            </div>
        @else
            <x-card padding="p-12">
                <x-empty-state 
                    icon="shield-check"
                    title="No Incident Records"
                    description="You have no incident reports matching the selected status."
                >
                    <x-button variant="primary" size="sm" href="{{ route('citizen.dashboard') }}" icon="plus">
                        Submit Incident Report
                    </x-button>
                </x-empty-state>
            </x-card>
        @endif

    </div>
</x-layouts.citizen>
