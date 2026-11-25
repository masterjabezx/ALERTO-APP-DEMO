@props([
    'align' => 'right',
    'width' => '48',
])

@php
    $alignmentClasses = match($align) {
        'left' => 'origin-top-left left-0',
        'top' => 'origin-top',
        default => 'origin-top-right right-0',
    };

    $widthClasses = match($width) {
        '56' => 'w-56',
        '64' => 'w-64',
        default => 'w-48',
    };
@endphp

<div class="relative" x-data="{ open: false }" x-on:click.outside="open = false" x-on:close.stop="open = false">
    <div x-on:click="open = !open">
        {{ $trigger }}
    </div>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="alerto-card absolute z-50 mt-2 {{ $widthClasses }} rounded-2xl shadow-[0_12px_28px_-4px_rgba(16,18,20,0.18)] bg-white border border-[#E6E3DE] py-1.5 focus:outline-none {{ $alignmentClasses }}"
        style="display: none;"
        x-on:click="open = false"
    >
        {{ $content }}
    </div>
</div>
