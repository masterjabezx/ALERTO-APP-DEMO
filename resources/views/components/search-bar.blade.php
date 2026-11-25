@props([
    'placeholder' => 'Search incidents, responders, or records...',
    'model' => 'search',
])

<div class="relative w-full" x-data="{ query: '' }">
    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
        <i data-lucide="search" class="w-4 h-4"></i>
    </div>

    <input
        type="text"
        x-model="query"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge([
            'class' => 'w-full py-2.5 pl-10 pr-9 text-sm rounded-xl border border-slate-200/90 bg-white placeholder-slate-400 text-slate-800 transition-colors focus:outline-none focus:ring-2 focus:ring-[#3187D8]/20 focus:border-[#3187D8] shadow-xs'
        ]) }}
    />

    <button
        type="button"
        x-show="query.length > 0"
        x-on:click="query = ''; $dispatch('search-cleared')"
        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors"
        x-cloak
    >
        <i data-lucide="x" class="w-4 h-4"></i>
    </button>
</div>

