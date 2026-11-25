@props([
    'height' => 'h-96',
    'title' => 'Hinoba-an Operations GIS Map',
    'lat' => '9.5936',
    'lng' => '122.4542',
    'zoom' => '13',
    'showControls' => true,
])

<div {{ $attributes->merge(['class' => "relative w-full {$height} rounded-[24px] overflow-hidden border border-slate-200/90 shadow-[0_6px_24px_-4px_rgba(23,57,87,0.08)] bg-slate-900"]) }}>
    {{-- Simulated Tactical GIS Canvas / Background Grid --}}
    <div class="absolute inset-0 bg-[#0f2438]">
        {{-- Tactical Grid Pattern --}}
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#1e3a54_1px,transparent_1px),linear-gradient(to_bottom,#1e3a54_1px,transparent_1px)] bg-[size:2.5rem_2.5rem] opacity-40"></div>
        
        {{-- Radar rings --}}
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 rounded-full border border-blue-500/10 pointer-events-none"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 rounded-full border border-blue-500/10 pointer-events-none"></div>

        {{-- Center Marker: Hinoba-an LDRRMO EOC --}}
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 flex flex-col items-center pointer-events-none">
            <span class="relative flex h-4 w-4">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#DB4545] opacity-75"></span>
                <span class="relative inline-flex rounded-full h-4 w-4 bg-[#DB4545] border-2 border-white shadow-lg"></span>
            </span>
            <div class="mt-2 px-2.5 py-1 rounded-lg bg-[#0f2438]/90 border border-white/20 backdrop-blur-xs text-[10px] font-mono text-white shadow-md whitespace-nowrap">
                Hinoba-an EOC (9.5936° N, 122.4542° E)
            </div>
        </div>
    </div>

    {{-- Controls Overlay --}}
    @if($showControls)
        <div class="absolute top-4 left-4 z-10 flex items-center gap-2">
            <div class="px-3 py-1.5 rounded-xl bg-white/95 border border-slate-200/80 shadow-md backdrop-blur-sm text-xs font-bold text-slate-800 flex items-center gap-1.5">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#19865B] opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-[#19865B]"></span>
                </span>
                <span>{{ $title }}</span>
            </div>
        </div>

        <div class="absolute top-4 right-4 z-10 flex flex-col gap-1.5">
            <button type="button" class="w-8 h-8 rounded-xl bg-white/95 border border-slate-200/80 shadow-md flex items-center justify-center text-slate-700 hover:bg-slate-50 transition-colors" title="Zoom In">
                <i data-lucide="plus" class="w-4 h-4"></i>
            </button>
            <button type="button" class="w-8 h-8 rounded-xl bg-white/95 border border-slate-200/80 shadow-md flex items-center justify-center text-slate-700 hover:bg-slate-50 transition-colors" title="Zoom Out">
                <i data-lucide="minus" class="w-4 h-4"></i>
            </button>
            <button type="button" class="w-8 h-8 rounded-xl bg-white/95 border border-slate-200/80 shadow-md flex items-center justify-center text-slate-700 hover:bg-slate-50 transition-colors" title="Recenter to EOC">
                <i data-lucide="crosshair" class="w-4 h-4"></i>
            </button>
        </div>

        <div class="absolute bottom-3 left-4 z-10 text-[10px] font-mono text-slate-400 bg-black/60 px-2.5 py-1 rounded-md backdrop-blur-xs">
            GIS Engine Ready · Hinoba-an LDRRMO Grid
        </div>
    @endif

    {{ $slot }}
</div>

