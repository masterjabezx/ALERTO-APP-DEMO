<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreAdminRequest;
use App\Http\Requests\SuperAdmin\StoreRoutingRuleRequest;
use App\Http\Requests\SuperAdmin\StoreSosRoutingRuleRequest;
use App\Models\AdminInvitation;
use App\Models\Barangay;
use App\Models\IncidentCategory;
use App\Models\IncidentRoutingRule;
use App\Models\Office;
use App\Models\Permission;
use App\Models\ResponseTeam;
use App\Models\SosRoutingRule;
use App\Models\SystemBackupRecord;
use App\Models\User;
use App\Services\AdminCommandCenterService;
use App\Services\SuperAdminAccountService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SuperAdminController extends Controller
{
    public function __construct(
        protected AdminCommandCenterService $commandService,
        protected SuperAdminAccountService $superAdminService
    ) {}

    public function dashboard(): View
    {
        $telemetry = $this->commandService->getDashboardTelemetry();
        $adminsCount = User::where('account_type', 'admin')->where('is_active', true)->count();
        $superAdminsCount = User::where('account_type', 'super_admin')->where('is_active', true)->count();
        $routingRulesCount = IncidentRoutingRule::where('is_active', true)->count();
        $sosRulesCount = SosRoutingRule::where('is_active', true)->count();
        $latestBackup = SystemBackupRecord::latest('created_at')->first();

        return view('super-admin.dashboard', compact(
            'telemetry',
            'adminsCount',
            'superAdminsCount',
            'routingRulesCount',
            'sosRulesCount',
            'latestBackup'
        ));
    }

    public function monitoring(Request $request): View
    {
        $accountType = $request->query('account_type', 'all');
        $statusFilter = $request->query('status', 'all');
        $monitor = $this->commandService->getLiveUserMonitorData(
            is_string($accountType) ? $accountType : null,
            is_string($statusFilter) ? $statusFilter : null
        );
        $telemetry = $this->commandService->getDashboardTelemetry();

        return view('super-admin.monitoring', compact('monitor', 'telemetry', 'accountType', 'statusFilter'));
    }

    public function adminsIndex(): View
    {
        $admins = User::with(['permissions', 'roles', 'userPresence'])
            ->whereIn('account_type', ['admin', 'super_admin'])
            ->orderByRaw("account_type = 'super_admin' DESC")
            ->orderBy('name')
            ->get();

        $invitations = AdminInvitation::with('inviter')
            ->latest('created_at')
            ->limit(15)
            ->get();

        $permissions = Permission::orderBy('group_name')->orderBy('name')->get();

        return view('super-admin.admins.index', compact('admins', 'invitations', 'permissions'));
    }

    public function storeAdmin(StoreAdminRequest $request): RedirectResponse
    {
        $user = $this->superAdminService->createAdminAccount(Auth::user(), $request->validated());

        return back()->with('success', "DIAL DAPH Command Center Admin account created for {$user->name}.");
    }

    public function updateAdmin(Request $request, int $id): RedirectResponse
    {
        $target = User::whereIn('account_type', ['admin', 'super_admin'])->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target->id)],
            'phone_number' => ['required', 'string', 'max:32', Rule::unique('users', 'phone_number')->ignore($target->id)],
            'password' => ['nullable', 'string', 'min:10'],
            'is_active' => ['nullable', 'boolean'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $this->superAdminService->updateAdminAccount(Auth::user(), $target, $validated);

        return back()->with('success', "Account {$target->name} updated cleanly.");
    }

    public function storeAdminInvitation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['nullable', 'string', 'max:32'],
            'expires_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $created = $this->superAdminService->createAdminInvitation(Auth::user(), $validated);
        $setupUrl = route('admin.setup.show', ['token' => $created['plain_token']]);

        return back()
            ->with('success', "Invitation created for {$created['invitation']->email}. Secure one-time setup URL generated below.")
            ->with('generated_setup_url', $setupUrl);
    }

    public function routingIndex(): View
    {
        $incidentRules = IncidentRoutingRule::with(['category', 'barangay', 'targetOffice', 'fallbackOffice', 'responseTeam'])
            ->latest('id')
            ->get();

        $sosRules = SosRoutingRule::with(['office', 'team'])
            ->orderBy('priority_order')
            ->get();

        $categories = IncidentCategory::orderBy('name')->get();
        $barangays = Barangay::orderBy('name')->get();
        $offices = Office::where('is_active', true)->with('teams')->orderBy('name')->get();
        $teams = ResponseTeam::where('is_active', true)->with('office')->orderBy('team_name')->get();

        return view('super-admin.routing.index', compact(
            'incidentRules',
            'sosRules',
            'categories',
            'barangays',
            'offices',
            'teams'
        ));
    }

    public function storeIncidentRoutingRule(StoreRoutingRuleRequest $request): RedirectResponse
    {
        $this->superAdminService->upsertIncidentRoutingRule(Auth::user(), $request->validated());

        return back()->with('success', 'Automated incident routing rule saved cleanly.');
    }

    public function deleteIncidentRoutingRule(int $id): RedirectResponse
    {
        IncidentRoutingRule::findOrFail($id)->delete();

        return back()->with('success', 'Incident routing rule removed.');
    }

    public function storeSosRoutingRule(StoreSosRoutingRuleRequest $request): RedirectResponse
    {
        $this->superAdminService->upsertSosRoutingRule(Auth::user(), $request->validated());

        return back()->with('success', 'Automated SOS routing rule saved cleanly.');
    }

    public function deleteSosRoutingRule(int $id): RedirectResponse
    {
        SosRoutingRule::findOrFail($id)->delete();

        return back()->with('success', 'SOS routing rule removed.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'slug' => ['nullable', 'string', 'max:64', 'unique:incident_categories,slug'],
            'description' => ['nullable', 'string', 'max:500'],
            'default_priority' => ['required', 'in:critical,high,medium,low'],
            'color_code' => ['nullable', 'string', 'max:16'],
            'icon' => ['nullable', 'string', 'max:64'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category = $this->superAdminService->upsertIncidentCategory(Auth::user(), $validated);

        return back()->with('success', "Incident category '{$category->name}' saved.");
    }

    public function updateCategory(Request $request, int $id): RedirectResponse
    {
        $category = IncidentCategory::findOrFail($id);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'description' => ['nullable', 'string', 'max:500'],
            'default_priority' => ['required', 'in:critical,high,medium,low'],
            'color_code' => ['nullable', 'string', 'max:16'],
            'icon' => ['nullable', 'string', 'max:64'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->superAdminService->upsertIncidentCategory(Auth::user(), $validated, $category);

        return back()->with('success', "Incident category '{$category->name}' updated.");
    }

    public function backupsIndex(): View
    {
        $backups = SystemBackupRecord::with('creator')->latest('created_at')->paginate(15);

        return view('super-admin.backups.index', compact('backups'));
    }

    public function createBackup(Request $request): RedirectResponse
    {
        $record = $this->superAdminService->createDatabaseBackup(Auth::user(), $request->input('notes'));

        return back()->with('success', "System backup '{$record->backup_name}' created cleanly.");
    }

    public function downloadBackup(int $id): BinaryFileResponse
    {
        $record = SystemBackupRecord::findOrFail($id);
        $disk = $record->disk ?: 'local';

        if (! Storage::disk($disk)->exists($record->file_path)) {
            abort(404, 'Backup snapshot file not found.');
        }

        return response()->download(Storage::disk($disk)->path($record->file_path), $record->backup_name);
    }

    public function restoreBackup(int $id): RedirectResponse
    {
        $record = SystemBackupRecord::findOrFail($id);

        try {
            $counts = $this->superAdminService->restoreDatabaseBackup(Auth::user(), $record);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Database restore completed across '.count($counts).' tables.');
    }
}
