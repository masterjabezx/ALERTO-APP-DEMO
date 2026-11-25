@extends('layouts.auth')

@section('title', 'Set Availability — ALERTO APP')

@section('content')
<script>
    if (typeof window.availabilityForm === 'undefined') {
        window.availabilityForm = function() {
            return {
                selected: '{{ old('status', $availability?->operational_status === 'available' ? 'online' : ($availability?->operational_status ? 'offline' : '')) }}',
                submitting: false,
                errorMessage: '',
                timeoutHandle: null,

                selectAndSubmit(status) {
                    if (this.submitting) return;
                    this.selected = status;
                    this.executeSubmit();
                },

                executeSubmit() {
                    if (!this.selected || this.submitting) return;

                    this.submitting = true;
                    this.errorMessage = '';

                    const controller = new AbortController();
                    let timedOut = false;

                    // Keep the timeout active until the full response body has been read.
                    clearTimeout(this.timeoutHandle);
                    this.timeoutHandle = setTimeout(() => {
                        timedOut = true;
                        controller.abort();
                    }, 8000);

                    const form = this.$refs.availabilityForm;
                    const url = form.action;
                    const token = form.querySelector('input[name="_token"]')?.value || '';

                    try {
                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token,
                            },
                            body: JSON.stringify({
                                status: this.selected,
                                _token: token,
                            }),
                            signal: controller.signal,
                        });
                        const data = await response.json().catch(() => null);

                        if (!response.ok) {
                            throw new Error(data?.message || `Unable to save availability (HTTP ${response.status}).`);
                        }

                        if (!data?.success || !data.redirect_url) {
                            throw new Error(data?.message || 'The server returned an incomplete availability response.');
                        }

                        window.location.assign(data.redirect_url);
                    } catch (error) {
                        this.submitting = false;
                        this.errorMessage = timedOut
                            ? 'The request timed out. Check your connection and try again.'
                            : (error?.message || 'Unable to save availability. Check your connection and try again.');
                    } finally {
                        clearTimeout(this.timeoutHandle);
                        this.timeoutHandle = null;
                    }
                }
            };
        };
    }
</script>

<div class="min-h-screen flex flex-col items-center justify-center bg-[#0B1726] px-4 py-10"
     style="font-family: 'Plus Jakarta Sans', sans-serif;"
     x-data="availabilityForm()">

    <div class="fixed inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#1e293b_1px,transparent_1px),linear-gradient(to_bottom,#1e293b_1px,transparent_1px)] bg-[size:4rem_4rem] opacity-20"></div>
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-[#3187D8]/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-2xl">

        {{-- Header --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[#173957] border border-[#3187D8]/30 mb-4 shadow-lg shadow-[#173957]/30">
                <svg class="w-7 h-7 text-[#3187D8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div class="flex items-center justify-center gap-2 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-[#3187D8] bg-[#3187D8]/10 px-2.5 py-0.5 rounded-full border border-[#3187D8]/20">
                    Field Operative Availability
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white mb-2">Welcome, {{ auth()->user()->name }}</h1>
            <p class="text-sm text-slate-400">Set your operational duty status to enter your command dashboard.</p>
        </div>

        {{-- Error Banner --}}
        <div x-show="errorMessage" style="display:none;"
             class="mb-6 flex items-start justify-between gap-3 p-4 rounded-2xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-sm">
            <div class="flex items-start gap-2.5">
                <svg class="w-5 h-5 mt-0.5 flex-shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span x-text="errorMessage"></span>
            </div>
            <button type="button" @click="errorMessage = ''" class="text-rose-400 hover:text-white">&times;</button>
        </div>

        @if(session('error'))
            <div class="mb-6 flex items-start gap-3 p-4 rounded-2xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-sm">
                <svg class="w-5 h-5 mt-0.5 flex-shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if (!$personnel)
            <div role="alert" class="space-y-2 rounded-2xl border border-amber-500/30 bg-amber-500/10 p-5 text-sm text-amber-100">
                <h2 class="font-bold">Personnel profile required</h2>
                <p class="text-amber-100/80">Your responder profile has not been configured. Contact your Office administrator before setting a duty status.</p>
            </div>
        @else
        {{-- Availability Selection Form --}}
        <form x-ref="availabilityForm" method="POST" action="{{ route('personnel.availability.update') }}" @submit.prevent="executeSubmit()">
            @csrf
            <input type="hidden" name="status" :value="selected">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                {{-- ONLINE OPTION --}}
                <div @click="selectAndSubmit('online')"
                     class="relative cursor-pointer p-6 sm:p-8 rounded-[22px] border-2 transition-all duration-200 text-center select-none bg-slate-900/80 hover:shadow-xl"
                     :class="selected === 'online'
                        ? 'border-emerald-500 bg-slate-900 shadow-xl shadow-emerald-500/15 scale-[1.02]'
                        : 'border-slate-800 hover:border-emerald-500/50 hover:bg-slate-900'">

                    <div class="flex items-center justify-center w-16 h-16 rounded-2xl mx-auto mb-5 transition-all duration-200"
                         :class="selected === 'online' ? 'bg-emerald-500/20 border border-emerald-500/50 text-emerald-300' : 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-400'">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z" />
                        </svg>
                    </div>

                    <h2 class="text-xl sm:text-2xl font-black mb-2 transition-colors"
                        :class="selected === 'online' ? 'text-emerald-300' : 'text-white'">
                        ONLINE
                    </h2>
                    <p class="text-xs text-slate-400 leading-relaxed mb-5">
                        Available for emergency dispatch.<br>Ready to receive assignments and alerts.
                    </p>

                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold"
                         :class="selected === 'online' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700'">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
                        </span>
                        <span>Click to Enter ONLINE</span>
                    </div>
                </div>

                {{-- OFFLINE OPTION --}}
                <div @click="selectAndSubmit('offline')"
                     class="relative cursor-pointer p-6 sm:p-8 rounded-[22px] border-2 transition-all duration-200 text-center select-none bg-slate-900/80 hover:shadow-xl"
                     :class="selected === 'offline'
                        ? 'border-slate-500 bg-slate-900 shadow-xl shadow-slate-500/10 scale-[1.02]'
                        : 'border-slate-800 hover:border-slate-600 hover:bg-slate-900'">

                    <div class="flex items-center justify-center w-16 h-16 rounded-2xl mx-auto mb-5 transition-all duration-200"
                         :class="selected === 'offline' ? 'bg-slate-700/50 border border-slate-500/50 text-slate-200' : 'bg-slate-800/80 border border-slate-700/40 text-slate-400'">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728M5.636 5.636a9 9 0 000 12.728m12.728 0l-12.728-12.728" />
                        </svg>
                    </div>

                    <h2 class="text-xl sm:text-2xl font-black mb-2 transition-colors"
                        :class="selected === 'offline' ? 'text-slate-200' : 'text-white'">
                        OFFLINE
                    </h2>
                    <p class="text-xs text-slate-400 leading-relaxed mb-5">
                        Off duty or taking a shift break.<br>Existing assigned incidents remain active.
                    </p>

                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold"
                         :class="selected === 'offline' ? 'bg-slate-700 text-slate-200 border border-slate-600' : 'bg-slate-800 text-slate-400 border border-slate-700'">
                        <span class="inline-flex rounded-full h-2 w-2 bg-slate-500"></span>
                        <span>Click to Enter OFFLINE</span>
                    </div>
                </div>
            </div>

            {{-- Confirm / Action Button --}}
            <div class="mt-8 text-center space-y-3">
                <button type="submit"
                        :disabled="!selected || submitting"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 py-4 px-12 rounded-2xl font-black text-sm transition-all duration-200 cursor-pointer shadow-lg"
                        :class="selected === 'online'
                            ? 'bg-emerald-500 hover:bg-emerald-400 text-slate-950 shadow-emerald-500/25'
                            : (selected === 'offline'
                                ? 'bg-slate-700 hover:bg-slate-600 text-white shadow-slate-700/25'
                                : 'bg-[#173957] hover:bg-[#1f4b72] text-white shadow-[#173957]/30')">
                    <svg x-show="submitting" style="display:none;" class="w-4 h-4 animate-spin text-current" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="submitting
                        ? 'Entering Personnel Portal...'
                        : (selected === 'online'
                            ? 'Confirm & Enter Dashboard as ONLINE'
                            : (selected === 'offline'
                                ? 'Confirm & Enter Dashboard as OFFLINE'
                                : 'Select a Status to Enter Dashboard'))"></span>
                </button>

                <p class="text-xs text-slate-500">
                    You can toggle or update your availability at any time from inside the Personnel Portal.
                </p>
            </div>
        </form>
        @endif
    </div>
</div>
@endsection
