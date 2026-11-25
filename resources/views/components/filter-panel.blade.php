@props([
    'title' => 'Filter Records',
])

<div
    x-data="{ isOpen: false }"
    class="relative"
>
    <button
        type="button"
        x-on:click="isOpen = !isOpen"
        class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl border border-slate-200/90 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors shadow-xs"
    >
        <i data-lucide="filter" class="w-3.5 h-3.5 text-slate-500"></i>
        <span>Filters</span>
        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="isOpen ? 'rotate-180' : ''"></i>
    </button>

    {{-- Dropdown Drawer --}}
    <div
        x-show="isOpen"
        x-on:click.outside="isOpen = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
        class="absolute right-0 z-30 mt-2 w-72 sm:w-80 rounded-[20px] bg-white border border-slate-200 shadow-[0_12px_32px_-4px_rgba(23,57,87,0.15)] p-5"
        x-cloak
    >
        <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">{{ $title }}</h4>
            <button
                type="button"
                x-on:click="isOpen = false"
                class="text-xs text-slate-400 hover:text-slate-600 font-semibold"
            >
                Done
            </button>
        </div>

        <div class="space-y-4">
            {{ $slot }}
        </div>
    </div>
</div>

