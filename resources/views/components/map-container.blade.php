@props([
    'height' => 'h-96',
    'title' => 'Hinoba-an Operations GIS Map',
    'lat' => '9.5936',
    'lng' => '122.4542',
    'zoom' => '13',
    'showControls' => true,
])

<div {{ $attributes->merge(['class' => "relative w-full {$height} rounded-[24px] overflow-hidden border border-[#E6E3DE] shadow-[0_8px_24px_-6px_rgba(16,18,20,0.16)] bg-[#101214]"]) }}>
    <div class="absolute inset-0 bg-[#17191C]">
        <div class="absolute inset-0 bg-[linear-gradient(to_right,rgba(232,117,36,0.08)_1px,transparent_1px),linear-gradient(to_bottom,rgba(232,117,36,0.08)_1px,transparent_1px)] bg-[size:2.5rem_2.5rem] opacity-55"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 rounded-full border border-[#E87524]/15 pointer-events-none"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 rounded-full border border-[#E87524]/10 pointer-events-none"></div>

        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 flex flex-col items-center pointer-events-none">
            <span class="relative flex h-4 w-4">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#C83F43] opacity-75"></span>
                <span class="relative inline-flex rounded-full h-4 w-4 bg-[#C83F43] border-2 border-white shadow-lg"></span>
            </span>
            <div class="mt-2 px-2.5 py-1 rounded-lg bg-[#101214]/90 border border-[#E87524]/30 backdrop-blur-xs text-[10px] font-mono text-[#F5F3EF] shadow-md whitespace-nowrap">
                Hinoba-an EOC (9°35'37.0"N 122°27'15.1"E)
            </div>
        </div>
    </div>

    @if($showControls)
        <div class="absolute top-4 left-4 z-10 flex items-center gap-2">
            <div class="px-3 py-1.5 rounded-xl bg-white/95 border border-[#E6E3DE] shadow-md text-xs font-bold text-[#17191C] flex items-center gap-1.5">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#27845B] opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-[#27845B]"></span>
                </span>
                <span>{{ $title }}</span>
            </div>
        </div>

        <div class="absolute top-4 right-4 z-10 flex flex-col gap-1.5">
            <button type="button" aria-label="Zoom In" class="w-8 h-8 rounded-xl bg-white/95 border border-[#E6E3DE] shadow-md flex items-center justify-center text-[#17191C] hover:border-[#E87524] transition-colors cursor-pointer">
                <i data-lucide="plus" class="w-4 h-4"></i>
            </button>
            <button type="button" aria-label="Zoom Out" class="w-8 h-8 rounded-xl bg-white/95 border border-[#E6E3DE] shadow-md flex items-center justify-center text-[#17191C] hover:border-[#E87524] transition-colors cursor-pointer">
                <i data-lucide="minus" class="w-4 h-4"></i>
            </button>
            <button type="button" aria-label="Recenter to EOC" class="w-8 h-8 rounded-xl bg-white/95 border border-[#E6E3DE] shadow-md flex items-center justify-center text-[#17191C] hover:border-[#E87524] transition-colors cursor-pointer">
                <i data-lucide="crosshair" class="w-4 h-4"></i>
            </button>
        </div>

        <div class="absolute bottom-3 left-4 z-10 text-[10px] font-mono text-[#F5F3EF]/80 bg-[#101214]/80 border border-white/10 px-2.5 py-1 rounded-md">
            GIS Engine Ready · Hinoba-an LDRRMO Grid
        </div>
    @endif

    {{ $slot }}
</div>
