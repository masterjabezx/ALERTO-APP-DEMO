@props(['hotlines' => [], 'compact' => false])

<div
    class="{{ $compact ? 'rounded-xl border border-amber-300/40 bg-amber-300/10 p-3 text-center' : 'w-full rounded-2xl border border-amber-300/40 bg-amber-300/10 p-5 text-center' }}"
    data-component="dual-hotlines"
    role="note"
>
    <p class="text-xs font-extrabold uppercase tracking-wider text-amber-200">Real hotline calls are disabled</p>
    <p class="mt-1 text-[11px] text-amber-100/80">This testing environment does not contact emergency services or responders.</p>
</div>
