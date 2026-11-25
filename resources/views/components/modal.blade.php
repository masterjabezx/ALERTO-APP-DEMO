@props([
    'name', // Unique identifier used with $dispatch('open-modal', 'name')
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
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-[#0f2438]/60 backdrop-blur-sm transition-opacity"
        x-on:click="show = false"
    ></div>

    {{-- Modal Box --}}
    <div
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="relative w-full bg-white rounded-[24px] shadow-[0_20px_40px_-8px_rgba(23,57,87,0.25)] border border-slate-200/80 overflow-hidden {{ $maxWidthClasses }} transform transition-all"
    >
        @if($title)
            <div class="px-6 py-4.5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900">{{ $title }}</h3>
                <button
                    type="button"
                    x-on:click="show = false"
                    class="text-slate-400 hover:text-slate-600 rounded-lg p-1 transition-colors"
                >
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        @endif

        <div class="p-6">
            {{ $slot }}
        </div>

        @if(isset($footer))
            <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>

