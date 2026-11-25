@props([
    'title',
    'value',
    'icon' => null,
    'trend' => null,
    'trendDirection' => 'neutral', // up, down, neutral
    'variant' => 'navy', // navy, blue, red, green, amber
    'description' => null,
])

@php
    $iconBg = match($variant) {
        'blue' => 'bg-[#3187D8]/10 text-[#3187D8] border border-[#3187D8]/20',
        'red', 'emergency' => 'bg-[#DB4545]/10 text-[#DB4545] border border-[#DB4545]/20',
        'green', 'success' => 'bg-[#19865B]/10 text-[#19865B] border border-[#19865B]/20',
        'amber', 'warning' => 'bg-[#F0A82E]/15 text-[#b3740e] border border-[#F0A82E]/30',
        default => 'bg-[#173957]/10 text-[#173957] border border-[#173957]/20',
    };
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-[20px] p-6 border border-slate-200/80 shadow-[0_4px_20px_-2px_rgba(23,57,87,0.06)] hover:shadow-[0_10px_28px_-4px_rgba(23,57,87,0.10)] transition-all duration-200']) }}>
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ $title }}</p>
            <h3 class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight" x-data x-count-up="{{ is_numeric($value) ? $value : '' }}">
                {{ $value }}
            </h3>

            @if($trend || $description)
                <div class="mt-3 flex items-center gap-2">
                    @if($trend)
                        <span class="inline-flex items-center text-xs font-semibold {{ $trendDirection === 'up' ? 'text-[#19865B]' : ($trendDirection === 'down' ? 'text-[#DB4545]' : 'text-slate-500') }}">
                            @if($trendDirection === 'up')
                                <i data-lucide="trending-up" class="w-3.5 h-3.5 mr-1"></i>
                            @elseif($trendDirection === 'down')
                                <i data-lucide="trending-down" class="w-3.5 h-3.5 mr-1"></i>
                            @endif
                            {{ $trend }}
                        </span>
                    @endif
                    @if($description)
                        <span class="text-xs text-slate-500">{{ $description }}</span>
                    @endif
                </div>
            @endif
        </div>

        @if($icon)
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0 {{ $iconBg }}">
                <i data-lucide="{{ $icon }}" class="w-6 h-6"></i>
            </div>
        @endif
    </div>
</div>

