@props([
    'placeholder' => 'Search incidents, responders, or records...',
    'model' => 'search',
])

<div class="relative w-full" x-data="{ query: '' }">
    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#77736E]">
        <i data-lucide="search" class="w-4 h-4"></i>
    </div>

    <input
        type="text"
        x-model="query"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge([
            'class' => 'w-full min-h-[42px] py-2 pl-10 pr-9 text-sm rounded-xl border border-[#E6E3DE] bg-white placeholder-[#77736E]/70 text-[#17191C] transition-colors focus:outline-none focus:ring-2 focus:ring-[#E87524]/20 focus:border-[#E87524]'
        ]) }}
    />

    <button
        type="button"
        x-show="query.length > 0"
        x-on:click="query = ''; $dispatch('search-cleared')"
        aria-label="Clear search"
        class="absolute inset-y-0 right-0 pr-3 flex items-center text-[#77736E] hover:text-[#17191C] transition-colors cursor-pointer"
        x-cloak
    >
        <i data-lucide="x" class="w-4 h-4"></i>
    </button>
</div>
