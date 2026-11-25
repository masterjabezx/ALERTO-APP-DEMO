<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuditLog;
use App\Models\PersonnelAvailability;
use App\Models\User;
use App\Models\UserPresence;
use App\Services\CitizenIncidentService;
use App\Services\HotlineService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function showCitizenLogin(HotlineService $hotlineService): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfAuthenticated('citizen')) {
            return $redirect;
        }

        $hotlines = $hotlineService->getDualHotlines();

        return view('auth.citizen-login', compact('hotlines'));
    }

    public function showPersonnelLogin(): View|RedirectResponse
    {
        return $this->redirectIfAuthenticated('personnel') ?? view('auth.personnel-login');
    }

    public function showOfficeLogin(): View|RedirectResponse
    {
        return $this->redirectIfAuthenticated('office') ?? view('auth.office-login');
    }

    public function showAdminLogin(): View|RedirectResponse
    {
        return $this->redirectIfAuthenticated('admin') ?? view('auth.admin-login');
    }

    public function showSuperAdminLogin(): View|RedirectResponse
    {
        return $this->redirectIfAuthenticated('super_admin') ?? view('auth.super-admin-login');
    }

    public function login(LoginRequest $request, string $portal): RedirectResponse
    {
        if (! in_array($portal, ['citizen', 'personnel', 'office', 'admin', 'super_admin'], true)) {
            abort(404);
        }

        $throttleKey = Str::transliterate($portal.'|'.$request->input('login').'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withInput($request->only('login', 'remember'))
                ->withErrors(['login' => "Too many login attempts. Please try again in {$seconds} seconds."]);
        }

        $loginInput = $request->input('login');
        $password = $request->input('password');

        // Look up by email OR phone_number
        $user = User::where('email', $loginInput)
            ->orWhere('phone_number', $loginInput)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withInput($request->only('login', 'remember'))
                ->withErrors(['login' => 'Invalid credentials provided.']);
        }

        // Strict role validation: Ensure user account_type strictly matches the portal
        $isAuthorized = ($user->account_type === $portal)
            || ($user->account_type === 'super_admin' && $portal === 'admin');

        if (! $isAuthorized) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withInput($request->only('login', 'remember'))
                ->withErrors(['login' => "This account is not authorized for the {$portal} portal."]);
        }

        // Account status check
        if (! $user->is_active) {
            return back()->withInput($request->only('login', 'remember'))
                ->withErrors(['login' => 'This account has been deactivated. Please contact an administrator.']);
        }

        RateLimiter::clear($throttleKey);

        $claimTokens = (array) $request->session()->get('guest_claim_tokens', []);
        if (is_string($request->input('guest_claim_token')) && $request->input('guest_claim_token') !== '') {
            $claimTokens[] = $request->input('guest_claim_token');
        }

        Auth::login($user, (bool) $request->boolean('remember'));
        $request->session()->regenerate();

        if ($portal === 'citizen' && $claimTokens !== []) {
            $claimed = app(CitizenIncidentService::class)->claimGuestReports($user, $claimTokens);
            $request->session()->forget(['guest_claim_tokens', 'guest_onboarding']);
            if ($claimed > 0) {
                $request->session()->flash('success', "Logged in and linked {$claimed} guest emergency report(s) to your Citizen account.");
            }
        }

        // Update presence
        UserPresence::updateOrCreate(
            ['user_id' => $user->id],
            [
                'status' => 'online',
                'current_device' => Str::limit($request->userAgent() ?? 'Web Browser', 128),
                'ip_address' => $request->ip(),
                'last_heartbeat_at' => now(),
            ]
        );

        // Audit log
        AuditLog::create([
            'user_id' => $user->id,
            'event_type' => 'auth:login',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'new_values' => ['portal' => $portal, 'ip' => $request->ip()],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return match ($portal) {
            'citizen' => redirect()->intended(route('citizen.dashboard')),
            'personnel' => redirect()->intended(route('personnel.availability')),
            'office' => redirect()->intended(route('office.dashboard')),
            'admin' => redirect()->intended($user->account_type === 'super_admin' ? route('super-admin.dashboard') : route('admin.dashboard')),
            'super_admin' => redirect()->intended(route('super-admin.dashboard')),
            default => redirect()->route('home'),
        };
    }

    public function logout(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();

            UserPresence::where('user_id', $user->id)->update([
                'status' => 'offline',
                'last_heartbeat_at' => now(),
            ]);

            if ($user->account_type === 'personnel' && $user->personnelProfile) {
                PersonnelAvailability::where('personnel_id', $user->personnelProfile->id)->update([
                    'operational_status' => 'off_duty',
                    'status_reason' => 'Logged out',
                    'status_updated_at' => now(),
                ]);
            }

            AuditLog::create([
                'user_id' => $user->id,
                'event_type' => 'auth:logout',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            Auth::logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have been successfully logged out.');
    }

    private function redirectIfAuthenticated(string $portal): ?RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();

            return match ($user->account_type) {
                'citizen' => redirect()->route('citizen.dashboard'),
                'personnel' => redirect()->route('personnel.availability'),
                'office' => redirect()->route('office.dashboard'),
                'admin' => redirect()->route('admin.dashboard'),
                'super_admin' => redirect()->route('super-admin.dashboard'),
                default => null,
            };
        }

        return null;
    }
}
