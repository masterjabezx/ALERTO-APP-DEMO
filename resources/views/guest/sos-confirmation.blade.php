@extends('layouts.auth', ['title' => 'Demo SOS Recorded — ALERTO APP'])

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10 space-y-6">
    <div class="rounded-3xl border-2 border-[#C83F43] bg-[#C83F43]/15 p-6 sm:p-8 text-white space-y-4">
        <span class="inline-block px-3 py-1 rounded-full bg-amber-300/10 text-amber-200 border border-amber-300/30 text-xs font-extrabold uppercase">Demo SOS Recorded — Not Dispatched</span>
        <h1 class="text-2xl sm:text-3xl font-extrabold">Test Reference: {{ $sos->reference_number }}</h1>
        <p class="text-sm text-[#F5F3EF]">
            This test record is stored only in the demo application. No SOS signal was sent to a real command center or responder.
        </p>
        @if($sos->latitude && $sos->longitude)
            <p class="text-xs font-mono-tactical text-[#F27D80]">
                Coordinates: {{ number_format((float) $sos->latitude, 6) }}, {{ number_format((float) $sos->longitude, 6) }}
                @if($sos->barangay) · {{ $sos->barangay->name }} @endif
            </p>
        @endif
    </div>

    <x-dual-hotlines :hotlines="$hotlines" />

    <div class="text-center">
        <a href="{{ route('home') }}" class="text-xs font-semibold text-[#D1CEC9] hover:text-[#F29A56] transition-colors">Return to Portal Gateway</a>
    </div>
</div>
@endsection
