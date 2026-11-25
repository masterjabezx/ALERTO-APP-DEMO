@extends('layouts.auth', ['title' => 'Emergency Guest Report — ALERTO APP'])

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8 space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('home') }}" class="text-xs font-semibold text-slate-400 hover:text-white">&larr; Back to Gateway</a>
        <div class="flex items-center gap-3">
            <a href="{{ route('citizen.login') }}" class="text-xs font-bold text-emerald-400 hover:text-emerald-300">Already registered? Login</a>
        </div>
    </div>

    {{-- Immediate Emergency SOS Widget --}}
    <x-sos-button-widget
        :endpoint="route('guest.sos.trigger')"
        sender-type="guest"
        :redirect-on-success="true"
    />

    {{-- Simplified First-Time Guest Report Form (No Category Selection!) --}}
    <div class="bg-slate-900/90 border border-slate-700 rounded-3xl p-6 sm:p-8 shadow-2xl">
        <div class="mb-6">
            <span class="inline-block px-3 py-1 rounded-full bg-emerald-500/15 text-emerald-300 text-xs font-bold uppercase tracking-wider mb-2">
                No Login Required for Your 1st Emergency Report
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white">Report an Emergency Immediately</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Provide your name, active callback phone number, and exact location pin. DIAL DAPH Command Center will call you right away to assess the emergency and dispatch help.
            </p>
        </div>

        <form action="{{ route('guest.report.submit') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-200 mb-1.5">Your Name *</label>
                    <input type="text" name="caller_name" value="{{ old('caller_name') }}" required placeholder="e.g., Juan Dela Cruz"
                           class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-200 mb-1.5">Active Phone Number (for Command Center Callback) *</label>
                    <input type="text" name="caller_phone" value="{{ old('caller_phone') }}" required placeholder="09171234567"
                           class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm focus:border-emerald-500 focus:outline-none">
                </div>
            </div>

            <x-emergency-location-picker />

            <div>
                <label class="block text-xs font-bold text-slate-200 mb-1.5">Optional Photo or Video Evidence</label>
                <input type="file" name="attachments[]" multiple accept="image/*,video/*"
                       class="w-full text-xs text-slate-300 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-800 file:text-emerald-300 hover:file:bg-slate-700">
                <p class="text-[11px] text-slate-500 mt-1">Attachments are optional — never delay an urgent emergency report if media is unavailable.</p>
            </div>

            <button type="submit"
                    class="w-full py-4 rounded-2xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-base shadow-lg shadow-emerald-500/25 transition-all cursor-pointer">
                SEND EMERGENCY REPORT NOW
            </button>
        </form>
    </div>

    {{-- Dual Direct Call Hotlines --}}
    <div class="space-y-3">
        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">Or Call Emergency Desks Directly</h2>
        <x-dual-hotlines :hotlines="$hotlines" />
    </div>
</div>
@endsection

