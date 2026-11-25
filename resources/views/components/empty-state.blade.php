@props([
    'icon' => 'inbox',
    'title' => 'No Data Available',
    'description' => 'There are currently no items or activity matching your criteria.',
])

<div {{ $attributes->merge(['class' => 'text-center py-10 px-4 max-w-md mx-auto']) }}>
    <div class="w-14 h-14 rounded-2xl bg-[#FFF0E4] text-[#B94F12] mx-auto mb-4 flex items-center justify-center border border-[#E87524]/25">
        <i data-lucide="{{ $icon }}" class="w-7 h-7"></i>
    </div>
    <h3 class="text-base font-extrabold text-[#17191C] mb-1">{{ $title }}</h3>
    <p class="text-xs text-[#77736E] leading-relaxed mb-5">{{ $description }}</p>

    @if($slot->isNotEmpty())
        <div class="flex items-center justify-center gap-3">
            {{ $slot }}
        </div>
    @endif
</div>
