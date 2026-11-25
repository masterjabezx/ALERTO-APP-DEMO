@props([
    'incident', // Incident model or mock data
    'category' => 'Medical Emergency',
    'reference' => 'INC-2026-0001',
    'location' => 'Poblacion 1, Hinoba-an',
    'status' => 'dispatched', // reported, verified, dispatched, on_scene, resolved, cancelled
    'priority' => 'high', // low, medium, high, critical
    'reportedAt' => '5 mins ago',
    'actions' => true,
])

@php
    $priorityColor = match($priority) {
        'critical' => 'text-[#DB4545] bg-[#DB4545]/10 border-[#DB4545]/20',
        'high' => 'text-[#F0A82E] bg-[#F0A82E]/10 border-[#F0A82E]/20',
        'medium' => 'text-[#3187D8] bg-[#3187D8]/10 border-[#3187D8]/20',
        default => 'text-slate-600 bg-slate-100 border-slate-200',
    };

    $statusVariant = match($status) {
        'reported' => 'warning',
        'verified', 'dispatched' => 'info',
        'on_scene' => 'warning',
        'resolved' => 'success',
        'cancelled' => 'neutral',
        default => 'neutral',
    };
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-[20px] p-5 border border-slate-200/80 shadow-[0_4px_20px_-2px_rgba(23,57,87,0.06)] hover:shadow-[0_12px_28px_-4px_rgba(23,57,87,0.12)] transition-all duration-200']) }}>
    <div class="flex items-start justify-between gap-3 mb-3">
        <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-xl bg-[#173957]/10 text-[#173957] flex items-center justify-center flex-shrink-0">
                <i data-lucide="alert-triangle" class="w-5 h-5"></i>
            </div>
            <div>
                <span class="text-xs font-mono font-semibold text-slate-500">{{ $reference }}</span>
                <h4 class="text-sm font-bold text-slate-900 line-clamp-1">{{ $category }}</h4>
            </div>
        </div>

        <x-badge :variant="$statusVariant" size="sm" :dot="in_array($status, ['reported', 'dispatched', 'on_scene'])" :pulse="in_array($status, ['reported', 'dispatched'])">
            {{ ucfirst(str_replace('_', ' ', $status)) }}
        </x-badge>
    </div>

    <div class="space-y-1.5 text-xs text-slate-600 mb-4">
        <div class="flex items-center gap-1.5">
            <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0"></i>
            <span class="truncate">{{ $location }}</span>
        </div>
        <div class="flex items-center justify-between pt-1">
            <div class="flex items-center gap-1.5">
                <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
                <span>{{ $reportedAt }}</span>
            </div>
            <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md border {{ $priorityColor }}">
                {{ $priority }} Priority
            </span>
        </div>
    </div>

    @if($actions)
        <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
            {{ $slot }}
        </div>
    @endif
</div>

