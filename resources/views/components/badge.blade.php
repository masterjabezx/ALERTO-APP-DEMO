@props([
    'variant' => 'neutral', // brand, success, emergency, warning, info, carbon, neutral
    'size' => 'md', // sm, md
    'dot' => false,
    'pulse' => false,
])

@php
    $baseClasses = 'inline-flex items-center font-bold rounded-full tracking-wide whitespace-nowrap';

    $sizeClasses = match($size) {
        'sm' => 'px-2.5 py-0.5 text-[10px] gap-1.5',
        default => 'px-3 py-1 text-xs gap-1.5',
    };

    $variantClasses = match($variant) {
        'brand', 'orange' => 'bg-[#FFF0E4] text-[#B94F12] border border-[#E87524]/30',
        'success' => 'bg-[#27845B]/12 text-[#27845B] border border-[#27845B]/25',
        'emergency', 'danger', 'critical' => 'bg-[#C83F43]/12 text-[#C83F43] border border-[#C83F43]/25',
        'warning' => 'bg-[#D99024]/15 text-[#9a630d] border border-[#D99024]/30',
        'info', 'blue' => 'bg-[#397BA8]/12 text-[#397BA8] border border-[#397BA8]/25',
        'carbon', 'navy' => 'bg-[#17191C] text-[#F5F3EF] border border-[#292D32] badge-fixed',
        default => 'bg-[#EFECE6] text-[#454A50] border border-[#E6E3DE]',
    };

    $dotColor = match($variant) {
        'brand', 'orange' => 'bg-[#E87524]',
        'success' => 'bg-[#27845B]',
        'emergency', 'danger', 'critical' => 'bg-[#C83F43]',
        'warning' => 'bg-[#D99024]',
        'info', 'blue' => 'bg-[#397BA8]',
        'carbon', 'navy' => 'bg-[#E87524]',
        default => 'bg-[#77736E]',
    };
@endphp

<span {{ $attributes->merge(['class' => "{$baseClasses} {$sizeClasses} {$variantClasses}"]) }}>
    @if($dot)
        <span class="relative flex h-2 w-2 shrink-0">
            @if($pulse)
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $dotColor }} opacity-75"></span>
            @endif
            <span class="relative inline-flex rounded-full h-2 w-2 {{ $dotColor }}"></span>
        </span>
    @endif
    <span>{{ $slot }}</span>
</span>
