@extends('layouts.auth', ['title' => 'Demo Report Recorded — ALERTO APP'])

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10 space-y-6">
    <div class="rounded-3xl border-2 border-[#27845B]/60 bg-[#292D32]/95 p-6 sm:p-8 shadow-2xl space-y-5">
        <div class="flex items-start gap-4">
            <div class="w-14 h-14 rounded-2xl bg-[#27845B]/20 border border-[#27845B]/50 text-[#5BD19A] flex items-center justify-center shrink-0">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-amber-200">Demo record saved — not sent to responders</span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white mt-1">Demo Report Recorded</h1>
                <p class="text-sm text-[#D1CEC9] mt-1">
                    This report is stored only in the testing environment. No call will be placed and no real responder or emergency service has been notified. Use synthetic test details only.
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
                    <span class="text-[#A39E98]">Test Location / Status</span>
                <p class="text-white font-semibold mt-0.5">
                    {{ $incident->barangay?->name ?? 'Unverified Test Area' }} · Test record
                </p>
            </div>
        </div>

        {{-- Demo citizen account onboarding --}}
        <div class="rounded-2xl border border-[#E87524]/40 bg-[#E87524]/12 p-5 space-y-3">
            <h2 class="text-base font-extrabold text-white">Link This Demo Report to a Test Citizen Account</h2>
            <p class="text-xs text-[#F5F3EF]/85">
                Use a synthetic account only. This report stays inside the testing application and will not be sent to an LDRRMO office or response unit.
            </p>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('citizen.register') }}" class="px-5 py-2.5 rounded-xl bg-[#E87524] hover:bg-[#D16419] text-white text-xs font-extrabold transition-colors">
                    Register Demo Citizen Account
                </a>
                <a href="{{ route('citizen.login') }}" class="px-5 py-2.5 rounded-xl bg-[#17191C] hover:bg-black/70 border border-white/20 text-white text-xs font-bold transition-colors">
                    Log In to Existing Citizen Account
                </a>
            </div>
        </div>
    </div>

    <div class="space-y-2">
        <h3 class="text-xs font-bold uppercase tracking-wider text-[#A39E98]">Test environment contact controls</h3>
        <x-dual-hotlines :hotlines="$hotlines" />
    </div>
</div>
@endsection
