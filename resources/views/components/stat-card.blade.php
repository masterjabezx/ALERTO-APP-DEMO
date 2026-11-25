@props([
    'title',
    'value',
    'icon' => null,
    'trend' => null,
    'trendDirection' => 'neutral', // up, down, neutral
    'variant' => 'brand', // brand, carbon, red, critical, green, success, amber, warning, blue, info
    'description' => null,
])

@php
    $iconBg = match($variant) {
        'brand', 'orange' => 'bg-[#FFF0E4] text-[#B94F12] border border-[#E87524]/25',
        'blue', 'info' => 'bg-[#397BA8]/12 text-[#397BA8] border border-[#397BA8]/25',
        'red', 'emergency', 'critical' => 'bg-[#C83F43]/12 text-[#C83F43] border border-[#C83F43]/25',
        'green', 'success' => 'bg-[#27845B]/12 text-[#27845B] border border-[#27845B]/25',
        'amber', 'warning' => 'bg-[#D99024]/15 text-[#9a630d] border border-[#D99024]/30',
        default => 'bg-[#17191C] text-[#F5F3EF] border border-[#292D32]',
    };

    $accentTop = match($variant) {
        'brand', 'orange' => 'border-t-2 border-t-[#E87524]',
        'red', 'emergency', 'critical' => 'border-t-2 border-t-[#C83F43]',
        'green', 'success' => 'border-t-2 border-t-[#27845B]',
        'amber', 'warning' => 'border-t-2 border-t-[#D99024]',
        'blue', 'info' => 'border-t-2 border-t-[#397BA8]',
        default => 'border-t-2 border-t-[#292D32]',
    };
@endphp

<div {{ $attributes->merge(['class' => "alerto-kpi-card alerto-card bg-white rounded-[20px] p-5 border border-[#E6E3DE] {$accentTop} alerto-interactive-card"]) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="flex-1 min-w-0">
            <p class="text-[11px] font-bold text-[#77736E] uppercase tracking-wider truncate">{{ $title }}</p>
            <h3 class="text-2xl sm:text-3xl font-black text-[#17191C] mt-1.5 tracking-tight font-mono"
                x-data
                @if(is_numeric($value)) x-count-up="{{ $value }}" @endif>
                {{ $value }}
            </h3>

            @if($trend || $description)
                <div class="mt-2.5 flex flex-wrap items-center gap-2">
                    @if($trend)
                        <span class="inline-flex items-center text-xs font-bold {{ $trendDirection === 'up' ? 'text-[#27845B]' : ($trendDirection === 'down' ? 'text-[#C83F43]' : 'text-[#77736E]') }}">
                            @if($trendDirection === 'up')
                                <i data-lucide="trending-up" class="w-3.5 h-3.5 mr-1"></i>
                            @elseif($trendDirection === 'down')
                                <i data-lucide="trending-down" class="w-3.5 h-3.5 mr-1"></i>
                            @endif
                            {{ $trend }}
                        </span>
                    @endif
                    @if($description)
                        <span class="text-[11px] text-[#77736E]">{{ $description }}</span>
                    @endif
                </div>
            @endif
        </div>

        @if($icon)
            <div class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 {{ $iconBg }}">
                <i data-lucide="{{ $icon }}" class="w-5 h-5"></i>
            </div>
        @endif
    </div>
</div>
