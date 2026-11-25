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
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs sm:text-sm font-bold flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs space-y-1">
                @foreach($errors->all() as $err)
                    <p class="font-semibold">&bull; {{ $err }}</p>
                @endforeach
            </div>
        @endif

        <div x-show="offlineSavedMsg" style="display:none;" class="p-4 rounded-2xl bg-amber-50 border-2 border-amber-400 text-amber-900 text-xs font-bold" x-text="offlineSavedMsg"></div>

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
        <div class="bg-white rounded-[24px] p-6 sm:p-8 border border-slate-200/80 shadow-[0_4px_20px_-2px_rgba(23,57,87,0.06)] space-y-5">
            <div class="border-b border-slate-100 pb-4">
                <span class="inline-block px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-extrabold uppercase tracking-wider">
                    Simplified Rapid Emergency Intake
                </span>
                <h1 class="text-xl sm:text-2xl font-black text-[#173957] mt-1.5">Report an Emergency Now</h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    No category forms needed. Confirm your name, callback number, and GPS/map location — DIAL DAPH Command Center will call you immediately to assess and dispatch responders.
                </p>
            </div>

            <form action="{{ route('citizen.incident.submit') }}" method="POST" enctype="multipart/form-data"
                  @submit="handleFormSubmit($event)" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Your Name *</label>
                        <input type="text" name="caller_name" value="{{ old('caller_name', $user->name) }}" required
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 text-sm focus:border-[#173957] focus:outline-none">
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700">Contact Phone Number *</label>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $user->is_phone_verified ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }}">
                                {{ $user->is_phone_verified ? 'Verified' : 'Unverified' }}
                            </span>
                        </div>
                        <input type="text" name="caller_phone" value="{{ old('caller_phone', $user->phone_number) }}" required
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 text-sm focus:border-[#173957] focus:outline-none">
                    </div>
                </div>

                {{-- Display-Only GPS Location Verification (Cannot Pin or Touch Map) --}}
                <div class="rounded-2xl bg-slate-900 p-4 text-white">
                    <x-emergency-location-picker
                        :default-lat="$primaryAddress?->latitude ?? 9.5897"
                        :default-lng="$primaryAddress?->longitude ?? 122.4675"
                        :gps-only="true"
                    />
                </div>

                <x-citizen-geo-media-capture :max-files="5" />

                <button type="submit"
                        class="w-full py-4 rounded-2xl bg-[#DB4545] hover:bg-[#c43838] text-white font-black text-base shadow-lg shadow-[#DB4545]/25 transition-all cursor-pointer">
                    SUBMIT EMERGENCY REPORT TO DIAL DAPH
                </button>
            </form>
        </div>

        {{-- 3. Dual Direct Call Hotlines (Square Side-by-Side) --}}
        <div class="bg-slate-900 rounded-[24px] p-5 sm:p-6 border border-slate-800 space-y-3">
            <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-300">Direct Voice Emergency Lines</h2>
            <x-dual-hotlines :hotlines="$hotlines" />
        </div>

        {{-- 4. Citizen Profile Contact Settings --}}
        <div class="bg-white rounded-[24px] p-6 border border-slate-200/80 shadow-xs space-y-4">
            <div>
                <h2 class="text-base font-extrabold text-[#173957]">My Saved Contact Details</h2>
                <p class="text-xs text-slate-500">Update the name and phone number pre-filled on future emergency reports.</p>
            </div>

            <form action="{{ route('citizen.profile.update') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                @csrf
                @method('PATCH')
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Full Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Mobile Phone Number</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" required
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-sm">
                </div>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#173957] hover:bg-[#1f4b72] text-white text-xs font-bold cursor-pointer">
                    Save Contact Details
                </button>
            </form>
        </div>
    </div>
</x-layouts.citizen>
