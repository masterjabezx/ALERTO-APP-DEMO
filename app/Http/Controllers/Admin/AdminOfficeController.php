<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOfficeRequest;
use App\Http\Requests\Admin\StoreOfficeUserRequest;
use App\Http\Requests\Admin\UpdateOfficeRequest;
use App\Http\Requests\Admin\UpdateOfficeUserRequest;
use App\Models\Barangay;
use App\Models\IncidentCategory;
use App\Models\Office;
use App\Models\OfficeUser;
use App\Services\OfficeManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminOfficeController extends Controller
{
    public function __construct(
        protected OfficeManagementService $officeService
    ) {}

    /**
     * Display office agencies directory with KPIs and filters.
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'department_type', 'is_active']);
        $offices = $this->officeService->getFilteredOffices($filters, 15);
        $stats = $this->officeService->getOfficeManagementStats();

        return view('admin.offices.index', [
            'offices' => $offices,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the office registration form.
     */
    public function create(): View
    {
        $barangays = Barangay::orderBy('name')->get();
        $categories = IncidentCategory::where('is_active', true)->orderBy('name')->get();

        return view('admin.offices.create', [
            'barangays' => $barangays,
            'categories' => $categories,
        ]);
    }

    /**
     * Store newly registered office.
     */
    public function store(StoreOfficeRequest $request): RedirectResponse
    {
        $office = $this->officeService->createOffice($request->validated(), Auth::user());

        return redirect()->route('admin.offices.index')
            ->with('success', "Office agency '{$office->name}' ({$office->code}) successfully registered.");
    }

    /**
     * Show edit form for an existing office.
     */
    public function edit(int $id): View
    {
        $office = Office::with(['location', 'responsibilities', 'authorizedBarangays'])->findOrFail($id);
        $barangays = Barangay::orderBy('name')->get();
        $categories = IncidentCategory::where('is_active', true)->orderBy('name')->get();

        return view('admin.offices.edit', [
            'office' => $office,
            'barangays' => $barangays,
            'categories' => $categories,
        ]);
    }

    /**
     * Update existing office attributes.
     */
    public function update(UpdateOfficeRequest $request, int $id): RedirectResponse
    {
        $office = Office::findOrFail($id);
        $this->officeService->updateOffice($office, $request->validated(), Auth::user());

        return redirect()->route('admin.offices.index')
            ->with('success', "Office agency '{$office->name}' updated successfully.");
    }

    /**
     * Toggle active/inactive status of an office.
     */
    public function toggleStatus(int $id): RedirectResponse
    {
        $office = Office::findOrFail($id);
        $newStatus = ! $office->is_active;

        $this->officeService->toggleOfficeStatus($office, $newStatus, Auth::user());

        $msg = $newStatus ? "Office '{$office->name}' activated." : "Office '{$office->name}' deactivated and associated staff set offline.";

        return back()->with('success', $msg);
    }

    /**
     * View and manage users for a specific office.
     */
    public function users(int $id): View
    {
        $office = Office::with(['officeUsers.user.userPresence', 'teams'])->findOrFail($id);

        return view('admin.offices.users', [
            'office' => $office,
        ]);
    }

    /**
     * Store new Office Admin or Office Staff account.
     */
    public function storeUser(StoreOfficeUserRequest $request, int $id): RedirectResponse
    {
        $office = Office::findOrFail($id);
        $user = $this->officeService->createOfficeUser($office, $request->validated(), Auth::user());

        return redirect()->route('admin.offices.users', $office->id)
            ->with('success', "Office account created for '{$user->name}' ({$user->email}).");
    }

    /**
     * Update an office user's permissions and profile.
     */
    public function updateUser(UpdateOfficeUserRequest $request, int $officeId, int $userId): RedirectResponse
    {
        $officeUser = OfficeUser::where('office_id', $officeId)->where('user_id', $userId)->firstOrFail();
        $this->officeService->updateOfficeUser($officeUser, $request->validated(), Auth::user());

        return redirect()->route('admin.offices.users', $officeId)
            ->with('success', "Office user '{$officeUser->user->name}' updated.");
    }

    /**
     * Toggle active status of an office user account.
     */
    public function toggleUserStatus(int $officeId, int $userId): RedirectResponse
    {
        $officeUser = OfficeUser::where('office_id', $officeId)->where('user_id', $userId)->firstOrFail();
        $newStatus = ! $officeUser->user->is_active;

        $this->officeService->toggleOfficeUserStatus($officeUser, $newStatus, Auth::user());

        $msg = $newStatus ? 'User account activated.' : 'User account deactivated.';

        return back()->with('success', $msg);
    }

    /**
     * Secure password reset for an office user.
     */
    public function resetUserPassword(Request $request, int $officeId, int $userId): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string', 'min:8']]);
        $officeUser = OfficeUser::where('office_id', $officeId)->where('user_id', $userId)->firstOrFail();

        $this->officeService->resetOfficeUserPassword($officeUser, $request->input('password'), Auth::user());

        return back()->with('success', "Password reset for '{$officeUser->user->name}'.");
    }
}
