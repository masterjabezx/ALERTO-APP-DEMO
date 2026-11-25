@props([
    'compact' => false,
])

<button
    type="button"
    x-data
    x-on:click="$store.theme?.toggle()"
    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white/80 dark:bg-white/5 hover:border-[#E87524]/60 text-xs font-bold transition-colors cursor-pointer touch-target"
    :aria-label="$store.theme?.isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
    title="Toggle Light / Dark Theme"
>
    <span x-show="$store.theme?.isDark" class="inline-flex items-center gap-1.5" x-cloak>
        <i data-lucide="sun" class="w-4 h-4 text-[#F29A56]"></i>
        @if(!$compact)
            <span class="hidden sm:inline">Light</span>
        @endif
    </span>
    <span x-show="!$store.theme?.isDark" class="inline-flex items-center gap-1.5">
        <i data-lucide="moon" class="w-4 h-4 text-[#B94F12]"></i>
        @if(!$compact)
            <span class="hidden sm:inline">Dark</span>
        @endif
    </span>
</button>

