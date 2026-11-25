@extends('layouts.auth')

@section('title', 'LDRRMO Super Admin Login — ALERTO APP')

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center bg-slate-950 px-4 py-10"
     x-data="{ submitting: false }">
    <div class="relative w-full max-w-md">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-white mb-6">
            &larr; Back to ALERTO Homepage
        </a>

        <div class="bg-slate-900/90 border border-purple-700/50 rounded-2xl p-8 shadow-2xl">
            <div class="p-3 rounded-xl bg-purple-950/60 border border-purple-700/40 text-xs text-purple-200 font-semibold mb-6">
                LDRRMO SUPER ADMIN — Highest System Governance & Oversight Authority
            </div>

            <h1 class="text-2xl font-extrabold text-white mb-1">LDRRMO Super Admin Login</h1>
            <p class="text-xs text-slate-400 mb-6">Hinoba-an Municipal Emergency System Governance</p>

            <form method="POST" action="{{ route('super-admin.login.post') }}" @submit="submitting = true" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Super Admin Email or Phone</label>
                    <input type="text" name="login" value="{{ old('login') }}" required autocomplete="username"
                           class="w-full px-4 py-3 rounded-xl bg-slate-800 border border-purple-800/60 text-white text-sm focus:border-purple-400 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Password</label>
                    <input type="password" name="password" required autocomplete="current-password"
                           class="w-full px-4 py-3 rounded-xl bg-slate-800 border border-purple-800/60 text-white text-sm focus:border-purple-400 focus:outline-none">
                </div>
                <label class="flex items-center gap-2 text-xs text-slate-400">
                    <input type="checkbox" name="remember" class="rounded bg-slate-800 border-slate-700 text-purple-500">
                    Remember session
                </label>
                <button type="submit" :disabled="submitting"
                        class="w-full py-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-sm shadow-lg transition-all cursor-pointer">
                    <span x-show="!submitting">Sign In as LDRRMO Super Admin</span>
                    <span x-show="submitting" style="display:none;">Authenticating...</span>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

