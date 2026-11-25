@extends('layouts.auth', ['title' => 'Emergency SOS Active — ALERTO APP'])

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10 space-y-6">
    <div class="rounded-3xl border-2 border-red-500 bg-red-950/60 p-6 sm:p-8 text-white space-y-4">
        <span class="inline-block px-3 py-1 rounded-full bg-red-500/30 text-red-200 text-xs font-extrabold uppercase">SOS Distress Transmitted</span>
        <h1 class="text-2xl sm:text-3xl font-extrabold">Reference: {{ $sos->reference_number }}</h1>
        <p class="text-sm text-rose-100">
            DIAL DAPH Command Center and LDRRMO Super Admin have received your SOS signal ({{ str_replace('_', ' ', $sos->location_status) }} location).
        </p>
        @if($sos->latitude && $sos->longitude)
            <p class="text-xs font-mono-tactical text-rose-200">
                Coordinates: {{ number_format((float) $sos->latitude, 6) }}, {{ number_format((float) $sos->longitude, 6) }}
                @if($sos->barangay) · {{ $sos->barangay->name }} @endif
            </p>
        @endif
    </div>

    <x-dual-hotlines :hotlines="$hotlines" />

    <div class="text-center">
        <a href="{{ route('home') }}" class="text-xs font-semibold text-slate-400 hover:text-white">Return to Portal Gateway</a>
    </div>
</div>
@endsection

