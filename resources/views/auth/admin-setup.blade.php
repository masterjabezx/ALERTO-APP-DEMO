@extends('layouts.auth', ['bodyClass' => 'bg-[#101214] text-[#F5F3EF] antialiased'])

@section('title', 'Activate DIAL DAPH Command Center Admin — ALERTO APP')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-[#101214] px-4 py-10 relative overflow-hidden">
    <div class="absolute inset-0 alerto-grid-canvas opacity-35 pointer-events-none"></div>
    <div class="relative w-full max-w-md bg-[#17191C] border border-[#E87524]/30 rounded-[24px] p-8 shadow-2xl">
        <div class="text-center mb-5">
            <img src="/images/branding/alerto-logo-web-256.png" alt="ALERTO APP" class="w-16 h-16 rounded-2xl object-contain mx-auto mb-3 drop-shadow-lg">
            <span class="inline-block px-3 py-1 rounded-full bg-[#E87524]/15 border border-[#E87524]/35 text-[#F29A56] text-xs font-bold">
                DIAL DAPH Command Center Account Setup
            </span>
        </div>

        @if(!$isValid)
            <h1 class="text-xl font-bold text-white mb-2">Invitation Link Invalid or Expired</h1>
            <p class="text-sm text-[#9D9993] mb-6">Ask your LDRRMO Super Admin to generate a new invitation setup link.</p>
            <a href="{{ route('home') }}" class="inline-block px-5 py-2.5 rounded-xl bg-[#292D32] hover:bg-[#454A50] text-white text-sm font-bold">Return Home</a>
        @else
            <h1 class="text-xl font-black text-white mb-1">Welcome, {{ $invitation->name }}</h1>
            <p class="text-xs text-[#9D9993] mb-6">Set your personal Command Center password for {{ $invitation->email }}.</p>

            <form method="POST" action="{{ route('admin.setup.store', ['token' => $token]) }}" class="space-y-4">
                @csrf
                @if(!$invitation->phone_number)
                    <div>
                        <label class="block text-xs font-bold text-[#D4D0C9] mb-1">Contact Mobile Number</label>
                        <input type="text" name="phone_number" value="{{ old('phone_number') }}" required placeholder="09171234567"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-[#101214] border border-white/15 text-white text-sm focus:border-[#E87524] focus:outline-none">
                    </div>
                @endif
                <div>
                    <label class="block text-xs font-bold text-[#D4D0C9] mb-1">Choose Password (min 10 characters)</label>
                    <input type="password" name="password" required minlength="10"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-[#101214] border border-white/15 text-white text-sm focus:border-[#E87524] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#D4D0C9] mb-1">Confirm Password</label>
                    <input type="password" name="password_confirmation" required minlength="10"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-[#101214] border border-white/15 text-white text-sm focus:border-[#E87524] focus:outline-none">
                </div>
                <button type="submit" class="w-full py-3 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white font-black text-sm shadow-lg cursor-pointer transition-colors">
                    Activate Admin Account
                </button>
            </form>
        @endif
    </div>
</div>
@endsection
