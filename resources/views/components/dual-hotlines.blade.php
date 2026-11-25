@props(['hotlines'])

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full" data-component="dual-hotlines">
    @foreach(['dial_daph', 'ldrrmo'] as $slotKey)
        @php
            $h = $hotlines[$slotKey] ?? ['title' => strtoupper($slotKey), 'number' => null, 'tel_href' => null, 'description' => '', 'is_configured' => false];
            $isDialDaph = $slotKey === 'dial_daph';
        @endphp

        @if(!empty($h['is_configured']) && !empty($h['tel_href']))
            <a href="{{ $h['tel_href'] }}"
               class="group relative flex flex-col items-center justify-center text-center aspect-[5/4] sm:aspect-square max-h-56 w-full rounded-2xl p-5 border-2 transition-all duration-200 shadow-lg hover:-translate-y-0.5
                      {{ $isDialDaph
                          ? 'bg-gradient-to-br from-rose-600 to-red-700 border-rose-400/60 text-white shadow-rose-600/25 hover:from-rose-500 hover:to-red-600'
                          : 'bg-gradient-to-br from-amber-500 to-orange-600 border-amber-300/60 text-white shadow-amber-500/25 hover:from-amber-400 hover:to-orange-500' }}">
                <div class="w-14 h-14 rounded-2xl bg-white/15 backdrop-blur-sm flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <span class="text-[11px] font-bold uppercase tracking-widest opacity-85">Emergency Voice Call</span>
                <span class="mt-1 text-base sm:text-lg font-extrabold tracking-tight leading-snug">
                    CALL {{ $h['title'] }}
                </span>
                <span class="mt-1.5 inline-flex items-center px-3 py-1 rounded-full bg-black/25 font-mono-tactical text-sm font-bold tracking-wide">
                    {{ $h['number'] }}
                </span>
                @if(!empty($h['description']))
                    <span class="mt-2 text-[11px] opacity-85 line-clamp-2 max-w-[22ch]">{{ $h['description'] }}</span>
                @endif
            </a>
        @else
            <div class="flex flex-col items-center justify-center text-center aspect-[5/4] sm:aspect-square max-h-56 w-full rounded-2xl p-5 border-2 border-dashed border-slate-400/50 bg-slate-800/40 text-slate-300">
                <div class="w-12 h-12 rounded-2xl bg-slate-700/60 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-sm font-bold text-slate-200">{{ $h['title'] }}</span>
                <span class="mt-1 text-xs font-medium text-amber-300">Hotline Not Configured</span>
                <span class="mt-1 text-[11px] text-slate-400">Command Center administrator has not activated this phone number yet.</span>
            </div>
        @endif
    @endforeach
</div>

