<x-layouts.citizen>
    <div class="max-w-2xl mx-auto space-y-6 text-center py-4">

        {{-- Success Graphic --}}
        <div class="w-20 h-20 rounded-full bg-[#27845B]/15 text-[#27845B] dark:text-[#5BD19A] border-4 border-[#27845B]/25 flex items-center justify-center mx-auto shadow-xl shadow-[#27845B]/10">
            <i data-lucide="check" class="w-10 h-10 stroke-[3]"></i>
        </div>

        <div class="space-y-1">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#27845B]/15 text-[#1B6644] dark:text-[#5BD19A] text-xs font-extrabold uppercase tracking-wider border border-[#27845B]/25">
                <span class="w-2 h-2 rounded-full bg-[#27845B] dark:bg-[#5BD19A]"></span>
                Report Received
            </span>
            <h1 class="text-3xl font-black text-[var(--text-primary)] tracking-tight">SUCCESSFUL REPORT SUBMISSION</h1>
            <p class="text-xs sm:text-sm text-[var(--text-secondary)] max-w-md mx-auto">
                Your incident report has been registered into the Hinoba-an LDRRMO intake queue.
            </p>
        </div>

        {{-- Incident Card Summary --}}
        <div class="alerto-card p-6 text-left space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-[var(--border-subtle)]">
                <div>
                    <span class="text-[10px] font-bold text-[var(--text-muted)] uppercase tracking-wider">Reference Number</span>
                    <p class="text-base font-mono font-extrabold text-[#B94F12] dark:text-[#F29A56]">{{ $incident->reference_number }}</p>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-bold text-[var(--text-muted)] uppercase tracking-wider">Status</span>
                    <p><x-badge variant="warning" size="sm" dot>PENDING REVIEW</x-badge></p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 text-xs">
                <div>
                    <span class="text-[var(--text-muted)] font-medium block">Classification Status</span>
                    <span class="font-bold text-[var(--text-primary)]">{{ $incident->category?->name ?? 'Awaiting DIAL DAPH Call Assessment' }}</span>
                </div>
                <div>
                    <span class="text-[var(--text-muted)] font-medium block">Verified Barangay</span>
                    <span class="font-bold text-[var(--text-primary)]">{{ $incident->barangay?->name ?? 'Unverified Area (GPS Captured)' }}</span>
                </div>
                <div>
                    <span class="text-[var(--text-muted)] font-medium block">Reported At</span>
                    <span class="font-bold text-[var(--text-primary)]">{{ $incident->submitted_at?->format('M d, Y · h:i A') }}</span>
                </div>
                <div>
                    <span class="text-[var(--text-muted)] font-medium block">Callback Number</span>
                    <span class="font-bold text-[var(--text-primary)]">{{ $incident->caller_phone }}</span>
                </div>
            </div>

            @if($incident->location)
                <div class="pt-2 text-xs text-[var(--text-secondary)] flex items-center gap-1.5 font-mono">
                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-[#C83F43]"></i>
                    <span>{{ $incident->location->latitude }}° N, {{ $incident->location->longitude }}° E ({{ str_replace('_', ' ', $incident->location->location_source) }})</span>
                </div>
            @endif
        </div>

        {{-- Dual Configurable Emergency Call Buttons --}}
        <div class="bg-[#17191C] rounded-[24px] p-5 border border-white/10 space-y-3 text-left text-[#F5F3EF]">
            <p class="text-xs font-bold uppercase tracking-wider text-[#D1CEC9]">Need Immediate Voice Assistance?</p>
            <x-dual-hotlines :hotlines="$hotlines" />
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
            <x-button variant="primary" size="lg" href="{{ route('citizen.records.show', $incident->reference_number) }}" icon="eye">
                Track My Incident Record
            </x-button>

            <x-button variant="outline" size="lg" href="{{ route('citizen.dashboard') }}" icon="plus">
                Submit Another Report
            </x-button>
        </div>

    </div>
</x-layouts.citizen>
