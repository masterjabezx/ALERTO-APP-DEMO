@props([
    'incident' => null,
    'category' => 'Medical Emergency',
    'reference' => 'INC-2026-0001',
    'location' => 'Poblacion 1, Hinoba-an',
    'status' => 'dispatched',
    'priority' => 'high',
    'reportedAt' => '5 mins ago',
    'actions' => true,
])

@php
    $priorityColor = match($priority) {
        'critical' => 'text-[#C83F43] bg-[#C83F43]/12 border-[#C83F43]/25',
        'high' => 'text-[#B94F12] bg-[#FFF0E4] border-[#E87524]/30',
        'medium' => 'text-[#397BA8] bg-[#397BA8]/12 border-[#397BA8]/25',
        default => 'text-[#454A50] bg-[#EFECE6] border-[#E6E3DE]',
    };

    $statusVariant = match($status) {
        'reported', 'pending_review' => 'warning',
        'verified', 'dispatched', 'accepted' => 'brand',
        'on_scene', 'in_progress' => 'info',
        'resolved' => 'success',
        'cancelled', 'rejected' => 'neutral',
        default => 'neutral',
    };
@endphp

<div {{ $attributes->merge(['class' => 'alerto-card alerto-interactive-card bg-white rounded-[20px] p-5 border border-[#E6E3DE]']) }}>
    <div class="flex items-start justify-between gap-3 mb-3">
        <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-10 h-10 rounded-xl bg-[#FFF0E4] text-[#B94F12] border border-[#E87524]/25 flex items-center justify-center shrink-0">
                <i data-lucide="alert-triangle" class="w-5 h-5"></i>
            </div>
            <div class="min-w-0">
                <span class="text-xs font-mono font-bold text-[#77736E]">{{ $reference }}</span>
                <h4 class="text-sm font-extrabold text-[#17191C] line-clamp-1">{{ $category }}</h4>
            </div>
        </div>

        <x-badge :variant="$statusVariant" size="sm" :dot="in_array($status, ['reported', 'pending_review', 'dispatched', 'on_scene'])" :pulse="in_array($status, ['reported', 'pending_review', 'dispatched'])">
            {{ ucfirst(str_replace('_', ' ', $status)) }}
        </x-badge>
    </div>

    <div class="space-y-1.5 text-xs text-[#454A50] mb-4">
        <div class="flex items-center gap-1.5">
            <i data-lucide="map-pin" class="w-3.5 h-3.5 text-[#E87524] shrink-0"></i>
            <span class="truncate">{{ $location }}</span>
        </div>
        <div class="flex items-center justify-between pt-1">
            <div class="flex items-center gap-1.5">
                <i data-lucide="clock" class="w-3.5 h-3.5 text-[#77736E]"></i>
                <span>{{ $reportedAt }}</span>
            </div>
            <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md border {{ $priorityColor }}">
                {{ $priority }} Priority
            </span>
        </div>
    </div>

    @if($actions)
        <div class="pt-3 border-t border-[#E6E3DE] flex items-center justify-between gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
