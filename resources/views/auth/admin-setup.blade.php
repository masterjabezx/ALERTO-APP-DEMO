@extends('layouts.auth')

@section('title', 'Activate DIAL DAPH Command Center Admin — ALERTO APP')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-slate-950 px-4 py-10">
    <div class="w-full max-w-md bg-slate-900 border border-rose-800/40 rounded-2xl p-8 shadow-2xl">
        <div class="text-center mb-4">
            <img src="/images/branding/alerto-logo-web-256.png" alt="ALERTO APP" class="w-16 h-16 rounded-2xl object-contain mx-auto mb-3 drop-shadow-lg">
            <span class="inline-block px-3 py-1 rounded-full bg-rose-500/15 text-rose-300 text-xs font-bold">
                DIAL DAPH Command Center Account Setup
            </span>
        </div>

        @if(!$isValid)
            <h1 class="text-xl font-bold text-white mb-2">Invitation Link Invalid or Expired</h1>
            <p class="text-sm text-slate-400 mb-6">Ask your LDRRMO Super Admin to generate a new invitation setup link.</p>
            <a href="{{ route('home') }}" class="inline-block px-5 py-2.5 rounded-xl bg-slate-800 text-white text-sm font-bold">Return Home</a>
        @else
            <h1 class="text-xl font-bold text-white mb-1">Welcome, {{ $invitation->name }}</h1>
            <p class="text-xs text-slate-400 mb-6">Set your personal Command Center password for {{ $invitation->email }}.</p>

            <form method="POST" action="{{ route('admin.setup.store', ['token' => $token]) }}" class="space-y-4">
                @csrf
                @if(!$invitation->phone_number)
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Contact Mobile Number</label>
                        <input type="text" name="phone_number" value="{{ old('phone_number') }}" required placeholder="09171234567"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-white text-sm">
                    </div>
                @endif
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Choose Password (min 10 characters)</label>
                    <input type="password" name="password" required minlength="10"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Confirm Password</label>
                    <input type="password" name="password_confirmation" required minlength="10"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-white text-sm">
                </div>
                <button type="submit" class="w-full py-3 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-sm cursor-pointer">
                    Activate Admin Account
                </button>
            </form>
        @endif
    </div>
</div>
@endsection

