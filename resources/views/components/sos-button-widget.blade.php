@props([
    'endpoint',
    'senderType' => 'citizen',
    'senderName' => null,
    'senderPhone' => null,
    'offlineQueueKey' => 'citizen_sos',
    'redirectOnSuccess' => false,
    'lastKnownLat' => null,
    'lastKnownLng' => null,
])

<div x-data="{
        holding: false,
        holdTimer: null,
        countdown: null,
        countdownInterval: null,
        sending: false,
        queuedOffline: false,
        sentReceipt: null,
        errorMsg: null,
        lat: @js($lastKnownLat),
        lng: @js($lastKnownLng),
        accuracy: null,
        locationStatus: @js(($lastKnownLat && $lastKnownLng) ? 'last_known_stale' : 'unavailable'),

        init() {
            this.refreshLiveGps();
        },

        refreshLiveGps() {
            if (!navigator.geolocation) return;
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.lat = pos.coords.latitude.toFixed(8);
                    this.lng = pos.coords.longitude.toFixed(8);
                    this.accuracy = Math.round(pos.coords.accuracy);
                    this.locationStatus = 'live';
                },
                () => {},
                { enableHighAccuracy: true, timeout: 7000 }
            );
        },

        startCountdown() {
            if (this.sending || this.countdown !== null) return;
            this.errorMsg = null;
            this.countdown = 3;
            this.countdownInterval = setInterval(() => {
                if (this.countdown > 1) {
                    this.countdown--;
                } else {
                    clearInterval(this.countdownInterval);
                    this.countdown = null;
                    this.transmitSos();
                }
            }, 1000);
        },

        cancelCountdown() {
            if (this.countdownInterval) {
                clearInterval(this.countdownInterval);
                this.countdownInterval = null;
            }
            this.countdown = null;
        },

        async transmitSos() {
            this.sending = true;
            this.errorMsg = null;

            const payload = {
                idempotency_key: 'sos-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10),
                sender_name: @js($senderName) || document.querySelector('[name=caller_name]')?.value || 'Emergency SOS Caller',
                sender_phone: @js($senderPhone) || document.querySelector('[name=caller_phone]')?.value || '',
                latitude: this.lat,
                longitude: this.lng,
                accuracy: this.accuracy,
                location_status: this.locationStatus,
                triggered_at: new Date().toISOString(),
            };

            if (!navigator.onLine) {
                await this.queueOfflineSos(payload);
                this.sending = false;
                return;
            }

            try {
                const res = await fetch(@js($endpoint), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || 'Unable to transmit SOS');
                }
                this.sentReceipt = data;
                if (@js($redirectOnSuccess) && data.confirmation_url) {
                    window.location.href = data.confirmation_url;
                }
            } catch (e) {
                await this.queueOfflineSos(payload);
            } finally {
                this.sending = false;
            }
        },

        async queueOfflineSos(payload) {
            try {
                if (window.AlertoOfflineStorage && typeof window.AlertoOfflineStorage.saveSosDraft === 'function') {
                    await window.AlertoOfflineStorage.saveSosDraft({ ...payload, sender_type: @js($senderType) });
                } else {
                    const existing = JSON.parse(localStorage.getItem('alerto_pending_sos') || '[]');
                    existing.push({ ...payload, sender_type: @js($senderType) });
                    localStorage.setItem('alerto_pending_sos', JSON.stringify(existing));
                }
                this.queuedOffline = true;
            } catch (err) {
                this.errorMsg = 'Offline storage unavailable. Please call DIAL DAPH hotline directly.';
            }
        }
     }"
     class="rounded-2xl border-2 border-red-500/40 bg-gradient-to-br from-red-950/90 via-rose-950/80 to-slate-950 p-5 text-white shadow-xl">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-red-500/20 border border-red-400/40 text-red-200 text-[11px] font-bold uppercase tracking-wider mb-2">
                <span class="w-2 h-2 rounded-full bg-red-400 animate-ping"></span>
                Immediate Life-Threatening Emergency
            </div>
            <h3 class="text-lg sm:text-xl font-extrabold text-white">
                {{ $senderType === 'personnel' ? 'RESPONDER DISTRESS SOS' : 'ONE-TAP EMERGENCY SOS' }}
            </h3>
            <p class="text-xs text-rose-200/80 mt-1">
                Sends instant distress alert with GPS telemetry directly to DIAL DAPH Command Center & LDRRMO Super Admin.
            </p>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px]">
                <span x-show="locationStatus === 'live'" class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-semibold">
                    Live GPS Ready (<span x-text="lat"></span>, <span x-text="lng"></span>)
                </span>
                <span x-show="locationStatus === 'last_known_stale'" class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-200 font-semibold">
                    Using Stale Last-Known Coordinates
                </span>
                <span x-show="locationStatus === 'unavailable'" class="px-2 py-0.5 rounded bg-rose-500/20 text-rose-200 font-semibold">
                    GPS Acquiring / Location Unavailable (SOS Still Sends)
                </span>
            </div>
        </div>

        <div class="flex flex-col items-stretch sm:items-end gap-2 shrink-0">
            <template x-if="countdown === null">
                <button type="button"
                        @click="startCountdown()"
                        :disabled="sending"
                        class="px-7 py-4 rounded-2xl bg-red-600 hover:bg-red-500 active:scale-95 text-white font-black text-base tracking-wide shadow-lg shadow-red-600/40 border-2 border-red-300/60 transition-all cursor-pointer disabled:opacity-50">
                    <span x-show="!sending">TRIGGER SOS NOW</span>
                    <span x-show="sending" style="display:none;">SENDING SOS...</span>
                </button>
            </template>

            <template x-if="countdown !== null">
                <div class="flex items-center gap-2">
                    <div class="px-4 py-3 rounded-xl bg-red-600 font-black text-sm animate-pulse">
                        Sending in <span x-text="countdown"></span>s...
                    </div>
                    <button type="button"
                            @click="cancelCountdown()"
                            class="px-4 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-600 text-xs font-bold text-white cursor-pointer">
                        CANCEL
                    </button>
                </div>
            </template>
        </div>
    </div>

    <div x-show="queuedOffline" style="display:none;" class="mt-4 p-3 rounded-xl bg-amber-500/20 border border-amber-400 text-amber-100 text-xs font-bold">
        SOS NOT YET RECEIVED BY COMMAND CENTER — You are offline. This SOS is queued locally and will transmit automatically as soon as connectivity returns. Please call emergency hotlines if voice network is available.
    </div>

    <div x-show="sentReceipt" style="display:none;" class="mt-4 p-3 rounded-xl bg-emerald-500/20 border border-emerald-400 text-emerald-100 text-xs font-bold">
        SOS RECEIVED BY DIAL DAPH COMMAND CENTER! Reference: <span class="font-mono-tactical" x-text="sentReceipt?.reference_number"></span>
        (<span x-text="sentReceipt?.location_status"></span> GPS). Help is being coordinated.
    </div>

    <div x-show="errorMsg" style="display:none;" class="mt-3 p-3 rounded-xl bg-rose-900 border border-rose-500 text-rose-100 text-xs" x-text="errorMsg"></div>
</div>

