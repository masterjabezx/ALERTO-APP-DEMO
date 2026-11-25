@props([
    'title' => 'Filter Records',
])

<div x-data="{ isOpen: false }" class="relative">
    <button
        type="button"
        x-on:click="isOpen = !isOpen"
        class="inline-flex items-center gap-2 min-h-[42px] px-3.5 py-2 rounded-xl border border-[#E6E3DE] bg-white text-xs font-bold text-[#292D32] hover:border-[#E87524]/50 hover:text-[#B94F12] transition-colors cursor-pointer"
    >
        <i data-lucide="filter" class="w-3.5 h-3.5 text-[#E87524]"></i>
        <span>Filters</span>
        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-[#77736E] transition-transform duration-200" :class="isOpen ? 'rotate-180' : ''"></i>
    </button>

    <div
        x-show="isOpen"
        x-on:click.outside="isOpen = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
        class="alerto-card absolute right-0 z-30 mt-2 w-72 sm:w-80 rounded-[20px] bg-white border border-[#E6E3DE] shadow-[0_14px_34px_-6px_rgba(16,18,20,0.18)] p-5"
        x-cloak
    >
        <div class="flex items-center justify-between pb-3 mb-3 border-b border-[#E6E3DE]">
            <h4 class="text-xs font-extrabold uppercase tracking-wider text-[#17191C]">{{ $title }}</h4>
            <button
                type="button"
                x-on:click="isOpen = false"
                class="text-xs text-[#B94F12] hover:text-[#E87524] font-bold cursor-pointer"
            >
                Done
            </button>
        </div>

        <div class="space-y-4">
            {{ $slot }}
        </div>
    </div>
</div>
