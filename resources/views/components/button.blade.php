@props([
    'variant' => 'primary', // primary, secondary, emergency, success, warning, outline, ghost
    'size' => 'md', // sm, md, lg
    'type' => 'button',
    'href' => null,
    'icon' => null,
    'iconPosition' => 'left',
    'loading' => false,
    'disabled' => false,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-semibold rounded-xl transition-all duration-200 cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed select-none touch-target';

    $sizeClasses = match($size) {
        'sm' => 'px-3 py-1.5 text-xs gap-1.5',
        'lg' => 'px-6 py-3.5 text-base gap-2.5',
        default => 'px-4 py-2.5 text-sm gap-2',
    };

    $variantClasses = match($variant) {
        'primary' => 'bg-[#173957] hover:bg-[#0f2438] text-white shadow-md shadow-[#173957]/20 focus:ring-[#173957]',
        'secondary' => 'bg-[#3187D8] hover:bg-[#2672ba] text-white shadow-md shadow-[#3187D8]/20 focus:ring-[#3187D8]',
        'emergency' => 'bg-[#DB4545] hover:bg-[#c43838] text-white shadow-lg shadow-[#DB4545]/25 focus:ring-[#DB4545]',
        'success' => 'bg-[#19865B] hover:bg-[#146e4b] text-white shadow-md shadow-[#19865B]/20 focus:ring-[#19865B]',
        'warning' => 'bg-[#F0A82E] hover:bg-[#d89423] text-white shadow-md shadow-[#F0A82E]/20 focus:ring-[#F0A82E]',
        'outline' => 'bg-transparent border border-slate-300 text-slate-700 hover:bg-slate-100 hover:text-slate-900 focus:ring-[#173957]',
        'ghost' => 'bg-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:ring-slate-300',
        default => 'bg-[#173957] text-white hover:bg-[#0f2438]',
    };

    $classes = "{$baseClasses} {$sizeClasses} {$variantClasses}";
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon && $iconPosition === 'left')
            <i data-lucide="{{ $icon }}" class="w-4 h-4 flex-shrink-0"></i>
        @endif
        <span>{{ $slot }}</span>
        @if($icon && $iconPosition === 'right')
            <i data-lucide="{{ $icon }}" class="w-4 h-4 flex-shrink-0"></i>
        @endif
    </a>
@else
    <button type="{{ $type }}" {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>
        @if($loading)
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-current" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        @elseif($icon && $iconPosition === 'left')
            <i data-lucide="{{ $icon }}" class="w-4 h-4 flex-shrink-0"></i>
        @endif
        <span>{{ $slot }}</span>
        @if($icon && $iconPosition === 'right')
            <i data-lucide="{{ $icon }}" class="w-4 h-4 flex-shrink-0"></i>
        @endif
    </button>
@endif

