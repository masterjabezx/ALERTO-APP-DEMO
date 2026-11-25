@props(['fixed' => false])

<div
    @class([
        'fixed inset-x-0 top-0 z-[1000]' => $fixed,
        'w-full border-b border-amber-300 bg-amber-100 text-center text-amber-950' => true,
    ])
    role="status"
    aria-label="ALERTO APP testing environment"
    aria-live="polite"
>
    <div class="mx-auto flex max-w-screen-xl flex-col items-center gap-0.5 px-3 py-2 sm:flex-row sm:justify-center sm:gap-2">
        <span class="text-[11px] font-black uppercase tracking-wider sm:text-xs">ALERTO APP — TESTING ENVIRONMENT</span>
        <span class="text-[11px] font-semibold sm:text-xs">Use synthetic data only. Device GPS, real calls, and real dispatches are disabled.</span>
    </div>
</div>
