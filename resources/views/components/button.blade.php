@props([
    'variant' => 'primary', // primary, secondary, emergency, danger, success, warning, info, outline, ghost
    'size' => 'md', // sm, md, lg
    'type' => 'button',
    'href' => null,
    'icon' => null,
    'iconPosition' => 'left',
    'loading' => false,
    'disabled' => false,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-bold rounded-xl alerto-btn-press cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E87524] focus-visible:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed select-none touch-target';

    $sizeClasses = match($size) {
        'sm' => 'px-3.5 py-2 text-xs gap-1.5 min-h-[36px]',
        'lg' => 'px-6 py-3.5 text-sm sm:text-base gap-2.5 min-h-[48px]',
        default => 'px-4 py-2.5 text-xs sm:text-sm gap-2 min-h-[42px]',
    };

    $variantClasses = match($variant) {
        'primary', 'brand' => 'bg-[#E87524] hover:bg-[#B94F12] text-white shadow-sm shadow-[#E87524]/20 btn-fixed',
        'secondary', 'carbon' => 'bg-[#17191C] hover:bg-[#292D32] text-white shadow-sm btn-fixed',
        'emergency', 'danger' => 'bg-[#C83F43] hover:bg-[#a93034] text-white shadow-md shadow-[#C83F43]/25 btn-fixed',
        'success' => 'bg-[#27845B] hover:bg-[#1e6a49] text-white shadow-sm shadow-[#27845B]/20 btn-fixed',
        'warning' => 'bg-[#D99024] hover:bg-[#b5761a] text-white shadow-sm btn-fixed',
        'info' => 'bg-[#397BA8] hover:bg-[#2d6389] text-white shadow-sm btn-fixed',
        'outline' => 'bg-transparent border border-[#E6E3DE] text-[#292D32] hover:bg-[#FFF0E4] hover:border-[#E87524]/50 hover:text-[#B94F12]',
        'ghost' => 'bg-transparent text-[#454A50] hover:bg-[#E6E3DE]/50 hover:text-[#17191C]',
        default => 'bg-[#E87524] hover:bg-[#B94F12] text-white shadow-sm btn-fixed',
    };

    $classes = "{$baseClasses} {$sizeClasses} {$variantClasses}";
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon && $iconPosition === 'left')
            <i data-lucide="{{ $icon }}" class="w-4 h-4 shrink-0"></i>
        @endif
        <span>{{ $slot }}</span>
        @if($icon && $iconPosition === 'right')
            <i data-lucide="{{ $icon }}" class="w-4 h-4 shrink-0"></i>
        @endif
    </a>
@else
    <button type="{{ $type }}" {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>
        @if($loading)
            <svg class="animate-spin -ml-1 mr-1.5 h-4 w-4 text-current" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        @elseif($icon && $iconPosition === 'left')
            <i data-lucide="{{ $icon }}" class="w-4 h-4 shrink-0"></i>
        @endif
        <span>{{ $slot }}</span>
        @if($icon && $iconPosition === 'right')
            <i data-lucide="{{ $icon }}" class="w-4 h-4 shrink-0"></i>
        @endif
    </button>
@endif
