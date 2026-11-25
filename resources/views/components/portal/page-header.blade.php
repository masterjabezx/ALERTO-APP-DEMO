@props([
    'eyebrow' => null,
    'title',
    'subtitle' => null,
    'breadcrumbs' => [],
    'badgeVariant' => 'brand',
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-3']) }}>
    @if(!empty($breadcrumbs))
        <x-portal.breadcrumbs :items="$breadcrumbs" />
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="min-w-0">
            @if($eyebrow)
                <x-badge :variant="$badgeVariant" size="sm" class="mb-2">
                    {{ $eyebrow }}
                </x-badge>
            @endif
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-[#17191C]">
                {{ $title }}
            </h1>
            @if($subtitle)
                <p class="text-xs sm:text-sm text-[#77736E] mt-1 max-w-3xl leading-relaxed">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        @if(isset($actions))
            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>

