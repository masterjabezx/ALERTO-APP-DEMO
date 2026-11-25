<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminInvitation;
use App\Services\SuperAdminAccountService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class AdminSetupController extends Controller
{
    public function show(string $token): View
    {
        $invitation = AdminInvitation::where('token_hash', hash('sha256', $token))->first();

        return view('auth.admin-setup', [
            'token' => $token,
            'invitation' => $invitation,
            'isValid' => $invitation !== null && $invitation->isValid(),
        ]);
    }

    public function store(Request $request, string $token, SuperAdminAccountService $service): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:10', 'confirmed'],
            'phone_number' => ['nullable', 'string', 'max:32', 'unique:users,phone_number'],
        ]);

        try {
            $user = $service->acceptAdminInvitation(
                $token,
                (string) $validated['password'],
                $validated['phone_number'] ?? null
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['token' => $e->getMessage()]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')
            ->with('success', 'Your DIAL DAPH Command Center Admin account is active.');
    }
}
