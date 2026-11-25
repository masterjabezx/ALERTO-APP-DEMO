@extends('layouts.auth', ['bodyClass' => 'bg-[#101214] text-[#F5F3EF] flex items-center justify-center min-h-screen py-10 px-4'])

@section('content')
<div class="w-full max-w-2xl" x-data="{
    loading: false,
    showPassword: false,
    lat: '{{ old('latitude', '9.58970000') }}',
    lng: '{{ old('longitude', '122.46750000') }}',
    locating: false,
    useDeviceLocation() {
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser.');
            return;
        }
        this.locating = true;
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                this.lat = pos.coords.latitude.toFixed(8);
                this.lng = pos.coords.longitude.toFixed(8);
                this.locating = false;
            },
            (err) => {
                alert('Could not retrieve GPS: ' + err.message);
                this.locating = false;
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    }
}">
    <!-- Header Navigation -->
    <div class="mb-6 flex justify-between items-center">
        <a href="{{ route('citizen.login') }}" class="inline-flex items-center text-xs font-medium text-slate-400 hover:text-white transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Citizen Login
        </a>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#E87524]/15 text-[#F29A56] border border-[#E87524]/35">
            Citizen Registration
        </span>
    </div>

    <!-- Registration Card -->
    <div class="bg-[#17191C] border border-white/10 rounded-[24px] p-6 sm:p-10 shadow-2xl">
        <div class="text-center mb-8">
            <img src="/images/branding/alerto-logo-web-256.png" alt="ALERTO APP" class="w-16 h-16 rounded-2xl object-contain mx-auto mb-3 drop-shadow-lg">
            <h2 class="text-2xl font-bold text-white tracking-tight">Create Citizen Profile</h2>
            <p class="text-xs text-slate-400 mt-1">Register for rapid emergency verification and localized safety alerts</p>
        </div>

        @if(!empty($guestOnboarding['reference_number']))
            <div class="mb-6 p-4 rounded-2xl bg-emerald-500/15 border border-emerald-400/40 text-xs text-emerald-200">
                <strong>Guest Report Linked on Completion:</strong> Reference <span class="font-mono-tactical font-bold">{{ $guestOnboarding['reference_number'] }}</span> will automatically be attached to your Citizen account once you register.
            </div>
        @endif

        <form action="{{ route('citizen.register.post') }}" method="POST" @submit="loading = true" class="space-y-6">
            @csrf
            @php
                $guestParts = preg_split('/\s+/', trim((string) ($guestOnboarding['name'] ?? '')), 2);
            @endphp

            <!-- Personal Name Block -->
            <div class="space-y-4">
                <h3 class="text-xs font-mono-tactical uppercase tracking-wider text-emerald-400 border-b border-slate-800 pb-1">1. Citizen Identity</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">First Name *</label>
                        <input type="text" name="first_name" value="{{ old('first_name', $guestParts[0] ?? '') }}" required
                            class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Last Name *</label>
                        <input type="text" name="last_name" value="{{ old('last_name', $guestParts[1] ?? '') }}" required
                            class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Middle Name (Optional)</label>
                        <input type="text" name="middle_name" value="{{ old('middle_name') }}"
                            class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Suffix (Jr., III, etc.)</label>
                        <input type="text" name="suffix" value="{{ old('suffix') }}"
                            class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Contact & Credentials Block -->
            <div class="space-y-4">
                <h3 class="text-xs font-mono-tactical uppercase tracking-wider text-emerald-400 border-b border-slate-800 pb-1">2. Contact & Authentication</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Mobile Phone Number *</label>
                        <input type="text" name="phone_number" value="{{ old('phone_number', $guestOnboarding['phone_number'] ?? '') }}" required placeholder="09171234567"
                            class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Email Address (Optional)</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="citizen@example.com"
                            class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Password *</label>
                        <input :type="showPassword ? 'text' : 'password'" name="password" required minlength="8"
                            class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Confirm Password *</label>
                        <input :type="showPassword ? 'text' : 'password'" name="password_confirmation" required minlength="8"
                            class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>
                <div class="flex items-center text-xs text-slate-400">
                    <input type="checkbox" @click="showPassword = !showPassword" id="show_pwd" class="rounded bg-slate-900 border-slate-700 text-emerald-500 mr-2">
                    <label for="show_pwd" class="cursor-pointer">Show Passwords</label>
                </div>
            </div>

            <!-- Mandatory Residential Location Pin Block -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-1">
                    <h3 class="text-xs font-mono-tactical uppercase tracking-wider text-emerald-400">3. Mandatory Residential Location Pin</h3>
                    <button type="button" @click="useDeviceLocation()" :disabled="locating"
                        class="text-xs inline-flex items-center text-emerald-400 hover:text-emerald-300 font-medium">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                        <span x-text="locating ? 'Acquiring GPS...' : 'Use My Current Location'"></span>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Resident Barangay *</label>
                        <select name="barangay_id" required class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm focus:border-emerald-500 focus:outline-none">
                            <option value="">-- Select Barangay --</option>
                            @foreach($barangays as $b)
                                <option value="{{ $b->id }}" {{ old('barangay_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">House / Street / Purok *</label>
                        <input type="text" name="street_address" value="{{ old('street_address') }}" required placeholder="Purok 4, Malakas St."
                            class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Residential Latitude *</label>
                        <input type="number" step="0.00000001" name="latitude" x-model="lat" required
                            class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm font-mono-tactical focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Residential Longitude *</label>
                        <input type="number" step="0.00000001" name="longitude" x-model="lng" required
                            class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm font-mono-tactical focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>
                <p class="text-[11px] text-slate-400">
                    <span class="text-amber-400 font-semibold">Privacy Notice:</span> Your residential pin is used strictly for fast emergency dispatch when an incident occurs at your residence. Citizens are <strong>never</strong> continuously tracked.
                </p>
            </div>

            <!-- Optional Emergency Data -->
            <div class="space-y-4">
                <h3 class="text-xs font-mono-tactical uppercase tracking-wider text-emerald-400 border-b border-slate-800 pb-1">4. Emergency Medical Contact (Optional)</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Blood Type</label>
                        <select name="blood_type" class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm focus:border-emerald-500 focus:outline-none">
                            <option value="">Unknown</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Emergency Contact Name</label>
                        <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" placeholder="Spouse / Parent Name"
                            class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Emergency Contact Phone</label>
                        <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}" placeholder="09179998877"
                            class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" :disabled="loading"
                class="w-full py-3.5 px-4 bg-[#E87524] hover:bg-[#B94F12] text-white font-bold rounded-xl shadow-lg shadow-[#E87524]/25 transition-all flex items-center justify-center space-x-2 disabled:opacity-50 cursor-pointer">
                <span x-show="!loading">Complete Citizen Registration</span>
                <span x-show="loading" style="display: none;" class="flex items-center space-x-2">
                    <svg class="animate-spin h-5 w-5 text-slate-950" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    <span>Registering Account...</span>
                </span>
            </button>
        </form>
    </div>
</div>
@endsection

