<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CitizenRegisterRequest;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\CitizenAddress;
use App\Models\CitizenProfile;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotificationSetting;
use App\Models\UserPresence;
use App\Services\CitizenIncidentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CitizenRegisterController extends Controller
{
    public function showRegistrationForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('citizen.dashboard');
        }

        $barangays = Barangay::orderBy('name')->get();
        $guestOnboarding = session('guest_onboarding', []);

        return view('auth.citizen-register', compact('barangays', 'guestOnboarding'));
    }

    public function register(CitizenRegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated, $request) {
            $middle = ! empty($validated['middle_name']) ? $validated['middle_name'].' ' : '';
            $suffix = ! empty($validated['suffix']) ? ' '.$validated['suffix'] : '';
            $fullName = trim($validated['first_name'].' '.$middle.$validated['last_name'].$suffix);

            $user = User::create([
                'name' => $fullName,
                'email' => $validated['email'] ?? null,
                'phone_number' => $validated['phone_number'],
                'password' => Hash::make($validated['password']),
                'account_type' => 'citizen',
                'is_active' => true,
                'phone_verified_at' => now(), // Auto-verified for local dev / initial registration
            ]);

            $citizenRole = Role::where('name', 'citizen')->first();
            if ($citizenRole) {
                $user->roles()->attach($citizenRole->id);
            }

            CitizenProfile::create([
                'user_id' => $user->id,
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'suffix' => $validated['suffix'] ?? null,
                'blood_type' => $validated['blood_type'] ?? null,
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
                'verified_citizen' => false,
            ]);

            CitizenAddress::create([
                'user_id' => $user->id,
                'barangay_id' => $validated['barangay_id'],
                'street_address' => $validated['street_address'],
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'is_primary' => true,
            ]);

            UserPresence::create([
                'user_id' => $user->id,
                'status' => 'online',
                'ip_address' => $request->ip(),
                'last_heartbeat_at' => now(),
            ]);

            UserNotificationSetting::create([
                'user_id' => $user->id,
                'enable_push' => true,
                'enable_sms' => true,
                'enable_email' => false,
                'enable_sound' => true,
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'event_type' => 'citizen:registered',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            return $user;
        });

        $claimTokens = (array) $request->session()->get('guest_claim_tokens', []);
        if (is_string($request->input('guest_claim_token')) && $request->input('guest_claim_token') !== '') {
            $claimTokens[] = $request->input('guest_claim_token');
        }

        Auth::login($user);
        $request->session()->regenerate();

        $claimedCount = 0;
        if ($claimTokens !== []) {
            $claimedCount = app(CitizenIncidentService::class)->claimGuestReports($user, $claimTokens);
            $request->session()->forget(['guest_claim_tokens', 'guest_onboarding']);
        }

        $message = $claimedCount > 0
            ? "Registration complete! Your account is linked with {$claimedCount} prior emergency report(s)."
            : 'Registration complete! Welcome to the ALERTO Emergency Network.';

        return redirect()->route('citizen.dashboard')->with('success', $message);
    }
}
