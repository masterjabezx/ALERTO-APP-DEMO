@props([
    'hoverable' => false,
    'padding' => 'p-6',
    'rounded' => 'rounded-[20px]',
])

@php
    $baseClasses = "bg-white border border-slate-200/80 {$rounded} {$padding} shadow-[0_4px_20px_-2px_rgba(23,57,87,0.06)]";
    $hoverClasses = $hoverable ? 'transition-all duration-200 hover:shadow-[0_12px_32px_-4px_rgba(23,57,87,0.12)] hover:-translate-y-0.5 hover:border-slate-300' : '';
@endphp

<div {{ $attributes->merge(['class' => "{$baseClasses} {$hoverClasses}"]) }}>
    @if(isset($header))
        <div class="border-b border-slate-100 pb-4 mb-5 flex items-center justify-between">
            {{ $header }}
        </div>
    @endif

    {{ $slot }}

    @if(isset($footer))
        <div class="border-t border-slate-100 pt-4 mt-5">
            {{ $footer }}
        </div>
    @endif
</div>

