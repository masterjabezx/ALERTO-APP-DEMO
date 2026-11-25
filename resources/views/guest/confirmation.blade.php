@extends('layouts.auth', ['title' => 'Emergency Report Received — ALERTO APP'])

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10 space-y-6">
    <div class="rounded-3xl border-2 border-[#27845B]/60 bg-[#292D32]/95 p-6 sm:p-8 shadow-2xl space-y-5">
        <div class="flex items-start gap-4">
            <div class="w-14 h-14 rounded-2xl bg-[#27845B]/20 border border-[#27845B]/50 text-[#5BD19A] flex items-center justify-center shrink-0">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-[#5BD19A]">Confirmed by DIAL DAPH Command Center Server</span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white mt-1">Emergency Report Received</h1>
                <p class="text-sm text-[#D1CEC9] mt-1">
                    Keep your phone line (<strong class="text-white">{{ $incident->caller_phone }}</strong>) nearby — a DIAL DAPH Command Center operator will call you to verify details and dispatch response teams.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 rounded-2xl bg-[#17191C] border border-white/10 p-4 text-xs">
            <div>
                <span class="text-[#A39E98]">Reference Number</span>
                <p class="font-mono-tactical font-extrabold text-[#5BD19A] text-base mt-0.5">{{ $incident->reference_number }}</p>
            </div>
            <div>
                <span class="text-[#A39E98]">Reported Coordinates</span>
                <p class="font-mono-tactical text-white mt-0.5">
                    {{ number_format((float) $incident->location?->latitude, 6) }}, {{ number_format((float) $incident->location?->longitude, 6) }}
                </p>
            </div>
            <div>
                <span class="text-[#A39E98]">Verified Location / Status</span>
                <p class="text-white font-semibold mt-0.5">
                    {{ $incident->barangay?->name ?? 'Unverified Area (GPS Captured)' }} · Awaiting Call Verification
                </p>
            </div>
        </div>

        {{-- Citizen Account Onboarding CTA (Required after 1st Guest Report) --}}
        <div class="rounded-2xl border border-[#E87524]/40 bg-[#E87524]/12 p-5 space-y-3">
            <h2 class="text-base font-extrabold text-white">Create or Log In to Your Citizen Account to Track This Report</h2>
            <p class="text-xs text-[#F5F3EF]/85">
                Your session holds a secure one-time verification token for <strong>{{ $incident->reference_number }}</strong>. Creating or logging in to your Citizen account right now automatically links this emergency report so you can track responder status and report future incidents quickly.
            </p>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('citizen.register') }}" class="px-5 py-2.5 rounded-xl bg-[#E87524] hover:bg-[#D16419] text-white text-xs font-extrabold transition-colors">
                    Register Citizen Account (Link This Report)
                </a>
                <a href="{{ route('citizen.login') }}" class="px-5 py-2.5 rounded-xl bg-[#17191C] hover:bg-black/70 border border-white/20 text-white text-xs font-bold transition-colors">
                    Log In to Existing Citizen Account
                </a>
            </div>
        </div>
    </div>

    <div class="space-y-2">
        <h3 class="text-xs font-bold uppercase tracking-wider text-[#A39E98]">Emergency Direct Call Hotlines</h3>
        <x-dual-hotlines :hotlines="$hotlines" />
    </div>
</div>
@endsection
