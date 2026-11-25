@extends('layouts.auth', ['title' => 'Emergency Guest Report — ALERTO APP'])

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8 space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('home') }}" class="text-xs font-semibold text-[#D1CEC9] hover:text-[#F29A56] transition-colors">&larr; Back to Gateway</a>
        <div class="flex items-center gap-3">
            <a href="{{ route('citizen.login') }}" class="text-xs font-bold text-[#F29A56] hover:text-white transition-colors">Already registered? Login</a>
        </div>
    </div>

    {{-- Immediate Emergency SOS Widget --}}
    <x-sos-button-widget
        :endpoint="route('guest.sos.trigger')"
        sender-type="guest"
        :redirect-on-success="true"
    />

    {{-- Simplified First-Time Guest Report Form (No Category Selection!) --}}
    <div class="bg-[#292D32]/90 border border-white/15 rounded-3xl p-6 sm:p-8 shadow-2xl">
        <div class="mb-6">
            <span class="inline-block px-3 py-1 rounded-full bg-[#E87524]/15 text-[#F29A56] text-xs font-bold uppercase tracking-wider mb-2 border border-[#E87524]/30">
                No Login Required for Your 1st Emergency Report
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white">Report an Emergency Immediately</h1>
            <p class="text-xs sm:text-sm text-[#D1CEC9] mt-1">
        Use synthetic test details only. This demo stores a test report and does not call DIAL DAPH or dispatch responders.
            </p>
        </div>

        <form action="{{ route('guest.report.submit') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-[#F5F3EF] mb-1.5">Your Name *</label>
                    <input type="text" name="caller_name" value="{{ old('caller_name') }}" required placeholder="e.g., Juan Dela Cruz"
                           class="w-full px-4 py-3 rounded-xl bg-[#17191C] border border-white/15 text-white text-sm placeholder:text-[#A39E98] focus-visible:border-[#E87524] outline-hidden">
                </div>
                <div>
            <label class="block text-xs font-bold text-[#F5F3EF] mb-1.5">Synthetic Test Phone Number *</label>
                    <input type="text" name="caller_phone" value="{{ old('caller_phone') }}" required placeholder="09171234567"
                           class="w-full px-4 py-3 rounded-xl bg-[#17191C] border border-white/15 text-white text-sm placeholder:text-[#A39E98] focus-visible:border-[#E87524] outline-hidden">
                </div>
            </div>

            <x-emergency-location-picker />

            <div>
                <label class="block text-xs font-bold text-[#F5F3EF] mb-1.5">Optional Photo or Video Evidence</label>
                <input type="file" name="attachments[]" multiple accept="image/*,video/*"
                       class="w-full text-xs text-[#D1CEC9] file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#17191C] file:text-[#F29A56] hover:file:bg-black/60 cursor-pointer">
                <p class="text-[11px] text-[#A39E98] mt-1">Attachments are optional — never delay an urgent emergency report if media is unavailable.</p>
            </div>

            <button type="submit"
                    class="w-full py-4 rounded-2xl bg-[#E87524] hover:bg-[#D16419] text-white font-black text-base shadow-lg shadow-[#E87524]/25 transition-all cursor-pointer">
                SEND EMERGENCY REPORT NOW
            </button>
        </form>
    </div>

    {{-- Dual Direct Call Hotlines --}}
    <div class="space-y-3">
        <h2 class="text-sm font-bold uppercase tracking-wider text-[#A39E98]">Or Call Emergency Desks Directly</h2>
        <x-dual-hotlines :hotlines="$hotlines" />
    </div>
</div>
@endsection
