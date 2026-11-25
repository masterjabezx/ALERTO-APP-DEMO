@props([
    'items' => [], // [['label' => '...', 'href' => '...'], ['label' => 'Current']]
])

<nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-1.5 text-xs font-medium text-[#77736E]">
    @foreach($items as $index => $item)
        @if($index > 0)
            <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-[#77736E]/60 shrink-0"></i>
        @endif

        @if(!empty($item['href']) && !$loop->last)
            <a href="{{ $item['href'] }}" class="hover:text-[#E87524] transition-colors truncate max-w-[180px]">
                {{ $item['label'] }}
            </a>
        @else
            <span class="font-bold text-[#17191C] truncate max-w-[220px]" aria-current="page">
                {{ $item['label'] }}
            </span>
        @endif
    @endforeach
</nav>

