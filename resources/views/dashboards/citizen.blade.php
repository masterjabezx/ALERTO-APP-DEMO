<x-layouts.citizen title="Citizen Emergency Portal — ALERTO Hinoba-an">
    <div class="space-y-6"
         x-data="{
            offlineSubmitting: false,
            offlineSavedMsg: null,
            handleFormSubmit(e) {
                if (navigator.onLine) return true;
                e.preventDefault();
                this.saveOfflineDraft(e.target);
                return false;
            },
            async saveOfflineDraft(form) {
                const fd = new FormData(form);
                const draft = {
                    client_uuid: 'draft-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8),
                    idempotency_key: 'inc-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10),
                    user_id: {{ (int) $user->id }},
                    caller_name: fd.get('caller_name'),
                    caller_phone: fd.get('caller_phone'),
                    latitude: fd.get('latitude'),
                    longitude: fd.get('longitude'),
                    accuracy: fd.get('accuracy'),
                    location_source: fd.get('location_source') || 'map_picker',
                    category_name: 'Simplified Report (Pending Assessment)',
                    description: 'Saved offline — awaiting Command Center call verification.',
                    status: 'queued',
                    created_at: new Date().toISOString(),
                };
                if (window.AlertoPwa && window.AlertoPwa.storage) {
                    await window.AlertoPwa.storage.saveIncidentDraft(draft);
                    this.offlineSavedMsg = 'Report saved on this device offline. It has NOT yet been received by DIAL DAPH Command Center and will auto-send when connection returns.';
                }
            }
         }">

        @if(session('success'))
            <div class="p-4 rounded-2xl bg-[#27845B]/12 border border-[#27845B]/30 text-[#1B6644] dark:text-[#5BD19A] text-xs sm:text-sm font-bold flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 text-[#27845B] dark:text-[#5BD19A] shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 rounded-2xl bg-[#C83F43]/12 border border-[#C83F43]/30 text-[#9F292D] dark:text-[#F27D80] text-xs space-y-1">
                @foreach($errors->all() as $err)
                    <p class="font-semibold">&bull; {{ $err }}</p>
                @endforeach
            </div>
        @endif

        <div x-show="offlineSavedMsg" style="display:none;" class="p-4 rounded-2xl bg-[#D99024]/15 border-2 border-[#D99024]/50 text-[#8A5409] dark:text-[#F6BA53] text-xs font-bold" x-text="offlineSavedMsg"></div>

        {{-- 1. Prominent Emergency Citizen SOS --}}
        <x-sos-button-widget
            :endpoint="route('citizen.sos.trigger')"
            sender-type="citizen"
            :sender-name="$user->name"
            :sender-phone="$user->phone_number"
            :last-known-lat="$primaryAddress?->latitude"
            :last-known-lng="$primaryAddress?->longitude"
        />

        {{-- 2. Simplified Emergency Incident Report (No Category Selection!) --}}
        <div class="alerto-card p-6 sm:p-8 space-y-5">
            <div class="border-b border-[var(--border-subtle)] pb-4">
                <span class="inline-block px-2.5 py-1 rounded-full bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56] text-[10px] font-extrabold uppercase tracking-wider border border-[#E87524]/25">
                    Simplified Rapid Emergency Intake
                </span>
                <h1 class="text-xl sm:text-2xl font-black text-[var(--text-primary)] mt-1.5">Report an Emergency Now</h1>
                <p class="text-xs text-[var(--text-secondary)] mt-0.5">
                    No category forms needed. Confirm your name, callback number, and GPS/map location — DIAL DAPH Command Center will call you immediately to assess and dispatch responders.
                </p>
            </div>

            <form action="{{ route('citizen.incident.submit') }}" method="POST" enctype="multipart/form-data"
                  @submit="handleFormSubmit($event)" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-[var(--text-primary)] mb-1">Your Name *</label>
                        <input type="text" name="caller_name" value="{{ old('caller_name', $user->name) }}" required
                               class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-panel)] border border-[var(--border-subtle)] text-[var(--text-primary)] text-sm focus-visible:border-[#E87524] focus-visible:ring-2 focus-visible:ring-[#E87524]/25 outline-hidden">
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-[var(--text-primary)]">Contact Phone Number *</label>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $user->is_phone_verified ? 'bg-[#27845B]/15 text-[#1B6644] dark:text-[#5BD19A]' : 'bg-[#D99024]/15 text-[#9A5D07] dark:text-[#F6BA53]' }}">
                                {{ $user->is_phone_verified ? 'Verified' : 'Unverified' }}
                            </span>
                        </div>
                        <input type="text" name="caller_phone" value="{{ old('caller_phone', $user->phone_number) }}" required
                               class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-panel)] border border-[var(--border-subtle)] text-[var(--text-primary)] text-sm focus-visible:border-[#E87524] focus-visible:ring-2 focus-visible:ring-[#E87524]/25 outline-hidden">
                    </div>
                </div>

                {{-- Display-Only GPS Location Verification (Cannot Pin or Touch Map) --}}
                <div class="rounded-2xl bg-[#17191C] border border-white/10 p-4 text-[#F5F3EF]">
                    <x-emergency-location-picker
                        :default-lat="$primaryAddress?->latitude ?? 9.5897"
                        :default-lng="$primaryAddress?->longitude ?? 122.4675"
                        :gps-only="true"
                    />
                </div>

                <x-citizen-geo-media-capture :max-files="5" />

                <button type="submit"
                        class="w-full py-4 rounded-2xl bg-[#C83F43] hover:bg-[#A83135] text-white font-black text-base shadow-lg shadow-[#C83F43]/25 transition-all cursor-pointer">
                    SUBMIT EMERGENCY REPORT TO DIAL DAPH
                </button>
            </form>
        </div>

        {{-- 3. Dual Direct Call Hotlines (Square Side-by-Side) --}}
        <div class="bg-[#17191C] rounded-[24px] p-5 sm:p-6 border border-white/10 space-y-3 text-[#F5F3EF]">
            <h2 class="text-xs font-extrabold uppercase tracking-wider text-[#D1CEC9]">Direct Voice Emergency Lines</h2>
            <x-dual-hotlines :hotlines="$hotlines" />
        </div>

        {{-- 4. Citizen Profile Contact Settings --}}
        <div class="alerto-card p-6 space-y-4">
            <div>
                <h2 class="text-base font-extrabold text-[var(--text-primary)]">My Saved Contact Details</h2>
                <p class="text-xs text-[var(--text-secondary)]">Update the name and phone number pre-filled on future emergency reports.</p>
            </div>

            <form action="{{ route('citizen.profile.update') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                @csrf
                @method('PATCH')
                <div>
                    <label class="block text-xs font-bold text-[var(--text-primary)] mb-1">Full Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-panel)] border border-[var(--border-subtle)] text-[var(--text-primary)] text-sm focus-visible:border-[#E87524] outline-hidden">
                </div>
                <div>
                    <label class="block text-xs font-bold text-[var(--text-primary)] mb-1">Mobile Phone Number</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" required
                           class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-panel)] border border-[var(--border-subtle)] text-[var(--text-primary)] text-sm focus-visible:border-[#E87524] outline-hidden">
                </div>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#17191C] hover:bg-[#E87524] text-white text-xs font-bold cursor-pointer transition-colors border border-white/10">
                    Save Contact Details
                </button>
            </form>
        </div>
    </div>
</x-layouts.citizen>
