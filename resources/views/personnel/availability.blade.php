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
                    if (this.submitting) {
                        return;
                    }
                    this.selected = status;
                    this.executeSubmit();
                },

                async executeSubmit() {
                    if (!this.selected || this.submitting) {
                        return;
                    }

                    this.submitting = true;
                    this.errorMessage = '';

                    const controller = new AbortController();
                    let timedOut = false;

                    clearTimeout(this.timeoutHandle);
                    this.timeoutHandle = setTimeout(() => {
                        timedOut = true;
                        controller.abort();
                    }, 8000);

                    const form = this.$refs.availabilityForm;
                    const url = form?.action;
                    const token = form?.querySelector('input[name="_token"]')?.value || '';

                    try {
                        const response = await fetch(url, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
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

<div class="min-h-screen flex flex-col items-center justify-center bg-[#17191C] text-[#F5F3EF] px-4 py-10 relative overflow-hidden"
     style="font-family: 'Plus Jakarta Sans', sans-serif;"
     x-data="availabilityForm()">

    <div class="fixed inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute inset-0 bg-[linear-gradient(to_right,rgba(255,255,255,0.035)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.035)_1px,transparent_1px)] bg-[size:3.5rem_3.5rem]"></div>
        <div class="absolute -top-24 left-1/4 w-96 h-96 bg-[#E87524]/15 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 right-1/4 w-80 h-80 bg-[#27845B]/12 rounded-full blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-2xl">

        {{-- Brand & Operative Header --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[#101214] border border-white/15 mb-4 shadow-xl">
                <img src="/images/branding/alerto-logo-web-128.png" alt="ALERTO APP" class="w-12 h-12 object-contain">
            </div>
            <div class="flex items-center justify-center gap-2 mb-2">
                <span class="text-[11px] font-black uppercase tracking-widest text-[#F29A56] bg-[#E87524]/15 px-3 py-1 rounded-full border border-[#E87524]/30">
                    Field Operative Availability
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight mb-2">Welcome, {{ auth()->user()->name }}</h1>
            <p class="text-sm text-[#D1CEC9]">Select your operational duty status to enter the Field Response Portal.</p>
        </div>

        {{-- Connection Status Notice --}}
        <template x-if="$store.pwa && ($store.pwa.status === 'connection_lost' || $store.pwa.status === 'reconnecting' || !$store.pwa.isOnline)">
            <div class="mb-5 p-3.5 rounded-2xl bg-[#D99024]/15 border border-[#D99024]/35 text-[#F6BA53] text-xs font-semibold flex items-center gap-2.5">
                <i data-lucide="wifi-off" class="w-4 h-4 shrink-0"></i>
                <span>Weak or offline signal detected — duty status update requires a brief server sync.</span>
            </div>
        </template>

        {{-- Error Banner --}}
        <div x-show="errorMessage" x-cloak
             role="alert"
             class="mb-6 flex items-start justify-between gap-3 p-4 rounded-2xl bg-[#C83F43]/15 border border-[#C83F43]/40 text-[#F27D80] text-sm">
            <div class="flex items-start gap-2.5">
                <svg class="w-5 h-5 mt-0.5 shrink-0 text-[#F27D80]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span x-text="errorMessage"></span>
            </div>
            <button type="button" @click="errorMessage = ''" class="text-[#F27D80] hover:text-white px-1 cursor-pointer" aria-label="Dismiss error">&times;</button>
        </div>

        @if(session('error'))
            <div role="alert" class="mb-6 flex items-start gap-3 p-4 rounded-2xl bg-[#C83F43]/15 border border-[#C83F43]/40 text-[#F27D80] text-sm">
                <svg class="w-5 h-5 mt-0.5 shrink-0 text-[#F27D80]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if (!$personnel)
            <div role="alert" class="space-y-2 rounded-2xl border border-[#D99024]/40 bg-[#D99024]/15 p-5 text-sm text-[#F6BA53]">
                <h2 class="font-bold">Personnel profile required</h2>
                <p class="text-[#F5F3EF]/85">Your responder profile has not been configured. Contact your Office administrator before setting a duty status.</p>
            </div>
        @else
        {{-- Availability Selection Form --}}
        <form x-ref="availabilityForm" method="POST" action="{{ route('personnel.availability.update') }}" @submit.prevent="executeSubmit()">
            @csrf
            <input type="hidden" name="status" :value="selected">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                {{-- ONLINE OPTION --}}
                <button type="button"
                        @click="selectAndSubmit('online')"
                        :disabled="submitting"
                        class="relative cursor-pointer p-6 sm:p-8 rounded-[22px] border-2 transition-all duration-200 text-center select-none bg-[#292D32]/90 hover:bg-[#292D32] focus-visible:ring-2 focus-visible:ring-[#27845B] outline-hidden disabled:opacity-75 disabled:cursor-wait"
                        :class="selected === 'online'
                            ? 'border-[#27845B] shadow-xl shadow-[#27845B]/20 scale-[1.01]'
                            : 'border-white/12 hover:border-[#27845B]/60'">

                    <div class="flex items-center justify-center w-16 h-16 rounded-2xl mx-auto mb-5 transition-all duration-200"
                         :class="selected === 'online' ? 'bg-[#27845B]/25 border border-[#27845B]/60 text-[#5BD19A]' : 'bg-[#27845B]/15 border border-[#27845B]/30 text-[#5BD19A]'">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z" />
                        </svg>
                    </div>

                    <h2 class="text-xl sm:text-2xl font-black mb-2 transition-colors"
                        :class="selected === 'online' ? 'text-[#5BD19A]' : 'text-white'">
                        ONLINE
                    </h2>
                    <p class="text-xs text-[#D1CEC9] leading-relaxed mb-5">
                        Available for emergency dispatch.<br>Ready to receive assignments and alerts.
                    </p>

                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold"
                         :class="selected === 'online' ? 'bg-[#27845B]/25 text-[#5BD19A] border border-[#27845B]/45' : 'bg-[#17191C] text-[#D1CEC9] border border-white/15'">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#5BD19A] opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-[#5BD19A]"></span>
                        </span>
                        <span x-text="submitting && selected === 'online' ? 'Activating ONLINE...' : 'Click to Enter ONLINE'"></span>
                    </div>
                </button>

                {{-- OFFLINE OPTION --}}
                <button type="button"
                        @click="selectAndSubmit('offline')"
                        :disabled="submitting"
                        class="relative cursor-pointer p-6 sm:p-8 rounded-[22px] border-2 transition-all duration-200 text-center select-none bg-[#292D32]/90 hover:bg-[#292D32] focus-visible:ring-2 focus-visible:ring-[#E87524] outline-hidden disabled:opacity-75 disabled:cursor-wait"
                        :class="selected === 'offline'
                            ? 'border-[#E87524] shadow-xl shadow-[#E87524]/15 scale-[1.01]'
                            : 'border-white/12 hover:border-[#E87524]/50'">

                    <div class="flex items-center justify-center w-16 h-16 rounded-2xl mx-auto mb-5 transition-all duration-200"
                         :class="selected === 'offline' ? 'bg-[#E87524]/25 border border-[#E87524]/50 text-[#F29A56]' : 'bg-white/5 border border-white/15 text-[#A39E98]'">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728M5.636 5.636a9 9 0 000 12.728m12.728 0l-12.728-12.728" />
                        </svg>
                    </div>

                    <h2 class="text-xl sm:text-2xl font-black mb-2 transition-colors"
                        :class="selected === 'offline' ? 'text-[#F29A56]' : 'text-white'">
                        OFFLINE
                    </h2>
                    <p class="text-xs text-[#D1CEC9] leading-relaxed mb-5">
                        Off duty or taking a shift break.<br>Existing assigned incidents remain active.
                    </p>

                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold"
                         :class="selected === 'offline' ? 'bg-[#E87524]/20 text-[#F29A56] border border-[#E87524]/40' : 'bg-[#17191C] text-[#D1CEC9] border border-white/15'">
                        <span class="inline-flex rounded-full h-2 w-2 bg-[#A39E98]"></span>
                        <span x-text="submitting && selected === 'offline' ? 'Saving OFFLINE...' : 'Click to Enter OFFLINE'"></span>
                    </div>
                </button>
            </div>

            {{-- Confirm / Action Button --}}
            <div class="mt-8 text-center space-y-3">
                <button type="submit"
                        :disabled="!selected || submitting"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 py-4 px-12 rounded-2xl font-black text-sm transition-all duration-200 cursor-pointer shadow-lg disabled:opacity-60"
                        :class="selected === 'online'
                            ? 'bg-[#27845B] hover:bg-[#1E6A49] text-white shadow-[#27845B]/25'
                            : (selected === 'offline'
                                ? 'bg-[#292D32] hover:bg-[#3A3F45] text-white border border-white/15'
                                : 'bg-[#E87524] hover:bg-[#D16419] text-white shadow-[#E87524]/25')">
                    <svg x-show="submitting" x-cloak class="w-4 h-4 animate-spin text-current" fill="none" viewBox="0 0 24 24">
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

                <p class="text-xs text-[#A39E98]">
                    You can toggle or update your availability at any time from inside the Personnel Portal.
                </p>
            </div>
        </form>
        @endif
    </div>
</div>
@endsection
