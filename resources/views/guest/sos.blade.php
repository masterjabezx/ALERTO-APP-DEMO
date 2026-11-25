@extends('layouts.auth', ['title' => 'Guest Emergency SOS — ALERTO APP'])

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8 space-y-6">
    <a href="{{ route('home') }}" class="inline-flex text-xs font-semibold text-slate-400 hover:text-white">&larr; Back to Gateway</a>

    <div class="bg-slate-900/90 border border-slate-700 rounded-3xl p-6 space-y-4">
        <h1 class="text-2xl font-extrabold text-white">Emergency SOS Assistance</h1>
        <p class="text-xs text-slate-300">Optionally provide your callback contact number before pressing Trigger SOS so Command Center operators can reach you.</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <input type="text" name="caller_name" placeholder="Your Name (Optional)" class="px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm">
            <input type="text" name="caller_phone" placeholder="Active Phone e.g. 09171234567" class="px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm">
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

