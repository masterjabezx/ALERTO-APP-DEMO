@props([
    'hoverable' => false,
    'padding' => 'p-6',
    'rounded' => 'rounded-[20px]',
])

@php
    $baseClasses = "alerto-card bg-white border border-[#E6E3DE] {$rounded} {$padding}";
    $hoverClasses = $hoverable ? 'alerto-interactive-card' : '';
@endphp

<div {{ $attributes->merge(['class' => "{$baseClasses} {$hoverClasses}"]) }}>
    @if(isset($header))
        <div class="border-b border-[#E6E3DE] pb-4 mb-5 flex items-center justify-between gap-3">
            {{ $header }}
        </div>
    @endif

    {{ $slot }}

    @if(isset($footer))
        <div class="border-t border-[#E6E3DE] pt-4 mt-5">
            {{ $footer }}
        </div>
    @endif
</div>
