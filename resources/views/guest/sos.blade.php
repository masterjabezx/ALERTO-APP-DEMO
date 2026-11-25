@extends('layouts.auth', ['title' => 'Guest Emergency SOS — ALERTO APP'])

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8 space-y-6">
    <a href="{{ route('home') }}" class="inline-flex text-xs font-semibold text-[#D1CEC9] hover:text-[#F29A56] transition-colors">&larr; Back to Gateway</a>

    <div class="bg-[#292D32]/90 border border-white/15 rounded-3xl p-6 space-y-4">
        <h1 class="text-2xl font-extrabold text-white">Emergency SOS Assistance</h1>
        <p class="text-xs text-[#D1CEC9]">Use synthetic details only. This demo stores a test SOS and does not contact command center staff or responders.</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <input type="text" name="caller_name" placeholder="Your Name (Optional)" class="px-3.5 py-2.5 rounded-xl bg-[#17191C] border border-white/15 text-white text-sm placeholder:text-[#A39E98] focus-visible:border-[#E87524] outline-hidden">
            <input type="text" name="caller_phone" placeholder="Active Phone e.g. 09171234567" class="px-3.5 py-2.5 rounded-xl bg-[#17191C] border border-white/15 text-white text-sm placeholder:text-[#A39E98] focus-visible:border-[#E87524] outline-hidden">
        </div>
    </div>

    <x-sos-button-widget
        :endpoint="route('guest.sos.trigger')"
        sender-type="guest"
        :redirect-on-success="true"
    />

    <x-dual-hotlines :hotlines="$hotlines" />
</div>
@endsection
