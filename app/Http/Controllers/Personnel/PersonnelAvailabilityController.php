<?php

declare(strict_types=1);

namespace App\Http\Controllers\Personnel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Personnel\UpdateAvailabilityRequest;
use App\Services\PersonnelManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class PersonnelAvailabilityController extends Controller
{
    public function __construct(
        protected PersonnelManagementService $personnelService
    ) {}

    public function show(): View
    {
        $user = Auth::user();
        $personnel = $user->personnelProfile;
        $availability = $personnel ? $personnel->availability : null;

        return view('personnel.availability', compact('personnel', 'availability'));
    }

    public function update(UpdateAvailabilityRequest $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            abort(401);
        }

        $personnel = $user->personnelProfile;
        if (! $personnel) {
            $message = 'Your Personnel profile is not configured. Contact your Office administrator.';

            if ($request->expectsJson() || $request->isJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 409);
            }

            return redirect()->route('personnel.availability');
        }

        $validated = $request->validated();
        $result = $this->personnelService->setPersonnelAvailability(
            $user,
            $validated['status'],
            $validated['reason'] ?? null
        );

        $message = $result['message'] ?? 'Availability status updated successfully.';

        if ($request->expectsJson() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect_url' => route('personnel.dashboard'),
            ]);
        }

        return redirect()->route('personnel.dashboard')->with('success', $message);
    }
}
