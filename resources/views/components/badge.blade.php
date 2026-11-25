@props([
    'variant' => 'neutral', // success, emergency, warning, info, neutral
    'size' => 'md', // sm, md
    'dot' => false,
    'pulse' => false,
])

@php
    $baseClasses = 'inline-flex items-center font-semibold rounded-full tracking-wide';

    $sizeClasses = match($size) {
        'sm' => 'px-2 py-0.5 text-[10px] gap-1',
        default => 'px-2.5 py-1 text-xs gap-1.5',
    };

    $variantClasses = match($variant) {
        'success' => 'bg-[#19865B]/10 text-[#19865B] border border-[#19865B]/20',
        'emergency', 'danger' => 'bg-[#DB4545]/10 text-[#DB4545] border border-[#DB4545]/20',
        'warning' => 'bg-[#F0A82E]/15 text-[#b3740e] border border-[#F0A82E]/30',
        'info', 'blue' => 'bg-[#3187D8]/10 text-[#3187D8] border border-[#3187D8]/20',
        'navy' => 'bg-[#173957]/10 text-[#173957] border border-[#173957]/20',
        default => 'bg-slate-100 text-slate-700 border border-slate-200',
    };

    $dotColor = match($variant) {
        'success' => 'bg-[#19865B]',
        'emergency', 'danger' => 'bg-[#DB4545]',
        'warning' => 'bg-[#F0A82E]',
        'info', 'blue' => 'bg-[#3187D8]',
        'navy' => 'bg-[#173957]',
        default => 'bg-slate-500',
    };
@endphp

<span {{ $attributes->merge(['class' => "{$baseClasses} {$sizeClasses} {$variantClasses}"]) }}>
    @if($dot)
        <span class="relative flex h-2 w-2">
            @if($pulse)
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $dotColor }} opacity-75"></span>
            @endif
            <span class="relative inline-flex rounded-full h-2 w-2 {{ $dotColor }}"></span>
        </span>
    @endif
    <span>{{ $slot }}</span>
</span>

