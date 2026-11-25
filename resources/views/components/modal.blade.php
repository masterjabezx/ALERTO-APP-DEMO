@props([
    'name',
    'title' => null,
    'maxWidth' => 'md', // sm, md, lg, xl, 2xl
])

@php
    $maxWidthClasses = match($maxWidth) {
        'sm' => 'sm:max-w-sm',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        default => 'sm:max-w-md',
    };
@endphp

<div
    x-data="{ show: false }"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') show = true"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}') show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div
        x-show="show"
        x-transition:enter="ease-out duration-250"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-[#101214]/70 backdrop-blur-xs transition-opacity"
        x-on:click="show = false"
    ></div>

    {{-- Modal Box --}}
    <div
        x-show="show"
        x-transition:enter="ease-out duration-250"
        x-transition:enter-start="opacity-0 translate-y-3 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-3 sm:translate-y-0 sm:scale-95"
        class="alerto-card relative w-full bg-white rounded-[24px] shadow-[0_24px_48px_-12px_rgba(16,18,20,0.35)] border border-[#E6E3DE] overflow-hidden {{ $maxWidthClasses }} transform transition-all"
    >
        @if($title)
            <div class="px-6 py-4 border-b border-[#E6E3DE] flex items-center justify-between gap-3">
                <h3 class="text-base font-extrabold text-[#17191C]">{{ $title }}</h3>
                <button
                    type="button"
                    x-on:click="show = false"
                    aria-label="Close modal"
                    class="text-[#77736E] hover:text-[#17191C] rounded-xl p-1.5 hover:bg-[#EFECE6] transition-colors cursor-pointer"
                >
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        @endif

        <div class="p-6">
            {{ $slot }}
        </div>

        @if(isset($footer))
            <div class="px-6 py-3.5 bg-[#FAF8F5] border-t border-[#E6E3DE] flex items-center justify-end gap-3">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
