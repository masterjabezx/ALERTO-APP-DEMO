<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Services\AdminCommandCenterService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function citizen(): View
    {
        $user = Auth::user();
        $profile = $user->citizenProfile;
        $address = $user->citizenAddresses()->where('is_primary', true)->first();

        return view('dashboards.citizen', compact('user', 'profile', 'address'));
    }

    public function personnel(): View
    {
        $user = Auth::user();
        $personnel = $user->personnelProfile;
        $availability = $personnel ? $personnel->availability : null;

        return view('dashboards.personnel', compact('user', 'personnel', 'availability'));
    }

    public function office(): View
    {
        $user = Auth::user();
        $officeUser = $user->officeUsers()->with('office')->first();

        return view('dashboards.office', compact('user', 'officeUser'));
    }

    public function admin(): View
    {
        $user = Auth::user();
        $commandService = app(AdminCommandCenterService::class);
        $telemetry = $commandService->getDashboardTelemetry();

        $pendingReview = Incident::with(['category', 'barangay'])
            ->whereIn('status', ['submitted', 'pending_review'])
            ->latest('submitted_at')
            ->limit(5)
            ->get();

        $categories = IncidentCategory::where('is_active', true)->orderBy('name')->get();
        $barangays = Barangay::orderBy('name')->get();

        return view('dashboards.admin', [
            'user' => $user,
            'stats' => $telemetry['stats'],
            'presence' => $telemetry['presence'],
            'charts' => $telemetry['charts'],
            'liveActivity' => $telemetry['live_activity'],
            'incomingReports' => $telemetry['incoming_reports'],
            'activeSosAlerts' => $telemetry['active_sos_alerts'],
            'highPriorityIncidents' => $telemetry['high_priority_incidents'],
            'recentIncidents' => $telemetry['recent_incidents'],
            'pendingReview' => $pendingReview,
            'categories' => $categories,
            'barangays' => $barangays,
        ]);
    }
}
