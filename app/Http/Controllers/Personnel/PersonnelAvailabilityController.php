<?php

declare(strict_types=1);

namespace App\Http\Controllers\Personnel;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\PersonnelProfile;
use App\Services\PersonnelManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

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

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $statusInput = $request->input('status', $request->input('operational_status'));

        $user = Auth::user();
        if (! $user) {
            abort(401);
        }

        $personnel = $user->personnelProfile;

        if (! $personnel) {
            $office = Office::first();
            $personnel = PersonnelProfile::create([
                'user_id' => $user->id,
                'office_id' => $office?->id ?? 1,
                'badge_number' => 'B-'.$user->id.'-'.time(),
                'rank_designation' => 'Field Operative',
            ]);
            $user->load('personnelProfile');
        }

        try {
            $result = $this->personnelService->setPersonnelAvailability(
                $user,
                (string) $statusInput,
                $request->input('reason')
            );
        } catch (InvalidArgumentException $e) {
            if ($request->expectsJson() || $request->isJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage())->withErrors(['status' => $e->getMessage()]);
        }

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
