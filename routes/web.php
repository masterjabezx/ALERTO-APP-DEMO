<?php

use App\Http\Controllers\Admin\AdminCommandCenterController;
use App\Http\Controllers\Admin\AdminFacebookMessengerController;
use App\Http\Controllers\Admin\AdminIncidentController;
use App\Http\Controllers\Admin\AdminLiveOperationsMapController;
use App\Http\Controllers\Admin\AdminMediaController;
use App\Http\Controllers\Admin\AdminOfficeController;
use App\Http\Controllers\Admin\AdminPersonnelController;
use App\Http\Controllers\Admin\AdminSosController;
use App\Http\Controllers\Auth\AdminSetupController;
use App\Http\Controllers\Auth\CitizenRegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LoginGatewayController;
use App\Http\Controllers\Citizen\CitizenPortalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuestEmergencyController;
use App\Http\Controllers\Map\RouteNavigationController;
use App\Http\Controllers\Media\MediaController;
use App\Http\Controllers\Messenger\InternalMessengerController;
use App\Http\Controllers\Office\OfficePortalController;
use App\Http\Controllers\Personnel\PersonnelAvailabilityController;
use App\Http\Controllers\Personnel\PersonnelPortalController;
use App\Http\Controllers\Pwa\PwaController;
use App\Http\Controllers\SuperAdmin\SuperAdminController;
use App\Http\Controllers\Webhooks\FacebookMessengerWebhookController;
use Illuminate\Support\Facades\Route;

// PWA & Offline Support Routes
Route::get('/offline', [PwaController::class, 'offline'])->name('pwa.offline');
Route::get('/pwa/ping', [PwaController::class, 'ping'])->name('pwa.ping');
Route::post('/pwa/connection-lost', [PwaController::class, 'reportConnectionLost'])->name('pwa.connection-lost');
Route::post('/pwa/sync/guest-sos', [PwaController::class, 'syncGuestSos'])->name('pwa.sync.guest-sos');

Route::middleware(['auth', 'account.active'])->prefix('pwa')->name('pwa.')->group(function () {
    Route::post('/sync/incident', [PwaController::class, 'syncIncident'])->middleware('throttle:media-upload')->name('sync.incident');
    Route::post('/sync/milestone', [PwaController::class, 'syncMilestone'])->name('sync.milestone');
    Route::post('/sync/sos', [PwaController::class, 'syncSos'])->name('sync.sos');
    Route::get('/tasks', [PwaController::class, 'tasks'])->name('tasks');
    Route::get('/office', [PwaController::class, 'office'])->name('office');
});

// 1. MAIN WEBSITE LOGIN GATEWAY & PUBLIC GUEST EMERGENCY REPORTING
Route::get('/', [LoginGatewayController::class, 'index'])->name('home');
Route::get('/report', [GuestEmergencyController::class, 'showReportForm'])->name('guest.report');
Route::post('/report', [GuestEmergencyController::class, 'submitReport'])->middleware('throttle:media-upload')->name('guest.report.submit');
Route::get('/report/confirmation/{reference}', [GuestEmergencyController::class, 'confirmation'])->name('guest.report.confirmation');
Route::get('/sos', [GuestEmergencyController::class, 'showSosForm'])->name('guest.sos');
Route::post('/sos', [GuestEmergencyController::class, 'triggerSos'])->name('guest.sos.trigger');
Route::get('/sos/confirmation/{reference}', [GuestEmergencyController::class, 'sosConfirmation'])->name('guest.sos.confirmation');

// Invited Command Center Admin Single-Use Account Setup
Route::get('/admin/setup/{token}', [AdminSetupController::class, 'show'])->name('admin.setup.show');
Route::post('/admin/setup/{token}', [AdminSetupController::class, 'store'])->name('admin.setup.store');

// 2. LOGOUT & PUBLIC ACCESS ENTRY POINTS
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/login', [LoginController::class, 'showCitizenLogin'])->name('login');

// DIAL DAPH Command Center dedicated operational login aliases
Route::middleware('guest')->group(function () {
    Route::get('/command-center/login', [LoginController::class, 'showAdminLogin'])->name('command-center.login');
    Route::post('/command-center/login', [LoginController::class, 'login'])->defaults('portal', 'admin')->name('command-center.login.post');
});

// 3. CITIZEN PORTAL ROUTES
Route::prefix('citizen')->name('citizen.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [LoginController::class, 'showCitizenLogin'])->name('login');
        Route::post('/login', [LoginController::class, 'login'])->defaults('portal', 'citizen')->name('login.post');
        Route::get('/register', [CitizenRegisterController::class, 'showRegistrationForm'])->name('register');
        Route::post('/register', [CitizenRegisterController::class, 'register'])->name('register.post');
    });

    Route::middleware(['auth', 'account.active', 'account.type:citizen'])->group(function () {
        Route::get('/dashboard', [CitizenPortalController::class, 'index'])->name('dashboard');
        Route::post('/incident', [CitizenPortalController::class, 'submitIncident'])->middleware('throttle:media-upload')->name('incident.submit');
        Route::post('/sos', [CitizenPortalController::class, 'triggerSos'])->name('sos.trigger');
        Route::patch('/profile', [CitizenPortalController::class, 'updateProfile'])->name('profile.update');
        Route::get('/incident/{reference}/success', [CitizenPortalController::class, 'submissionSuccess'])->name('incident.success');
        Route::get('/records', [CitizenPortalController::class, 'records'])->name('records');
        Route::get('/records/{reference}', [CitizenPortalController::class, 'showRecord'])->name('records.show');
        Route::post('/records/{reference}/evidence', [MediaController::class, 'addCitizenEvidence'])->middleware('throttle:media-upload')->name('records.evidence.add');
        Route::delete('/records/{reference}/evidence/{media}', [MediaController::class, 'deleteCitizenEvidence'])->name('records.evidence.delete');
        Route::get('/contacts', [CitizenPortalController::class, 'contacts'])->name('contacts');
    });
});

// 4. PERSONNEL PORTAL ROUTES
Route::prefix('personnel')->name('personnel.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [LoginController::class, 'showPersonnelLogin'])->name('login');
        Route::post('/login', [LoginController::class, 'login'])->defaults('portal', 'personnel')->name('login.post');
    });

    Route::middleware(['auth', 'account.active', 'account.type:personnel'])->group(function () {
        Route::get('/availability', [PersonnelAvailabilityController::class, 'show'])->name('availability');
        Route::post('/availability', [PersonnelAvailabilityController::class, 'update'])->name('availability.update');
        Route::get('/dashboard', [PersonnelPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/tasks', [PersonnelPortalController::class, 'dashboard'])->name('tasks');
        Route::get('/active-incidents', [PersonnelPortalController::class, 'activeIncidents'])->name('active-incidents');
        Route::get('/map', [PersonnelPortalController::class, 'map'])->name('map');
        Route::get('/map/data', [PersonnelPortalController::class, 'mapData'])->name('map.data');
        Route::get('/records', [PersonnelPortalController::class, 'records'])->name('records');
        Route::post('/assignments/{id}/milestone', [PersonnelPortalController::class, 'updateMilestone'])->name('milestone');
        Route::post('/sos', [PersonnelPortalController::class, 'triggerSos'])->name('sos.trigger');
        Route::post('/location', [PersonnelPortalController::class, 'updateLocation'])->name('location');
        Route::post('/heartbeat', [PersonnelPortalController::class, 'heartbeat'])->name('heartbeat');

        // Internal Messenger
        Route::get('/messages', [InternalMessengerController::class, 'inbox'])->name('messages');
        Route::get('/messages/{conversation}', [InternalMessengerController::class, 'show'])->name('messages.show');
        Route::post('/messages/{conversation}/send', [InternalMessengerController::class, 'storeMessage'])->name('messages.send');
        Route::get('/messages/direct/{userId}', [InternalMessengerController::class, 'startDirect'])->name('messages.direct');
        Route::get('/messages/incident/{reference}', [InternalMessengerController::class, 'startIncidentConversation'])->name('messages.incident');
        Route::post('/messages/{conversation}/read', [InternalMessengerController::class, 'markAsRead'])->name('messages.read');
    });
});

// 5. OFFICE PORTAL ROUTES
Route::prefix('office')->name('office.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [LoginController::class, 'showOfficeLogin'])->name('login');
        Route::post('/login', [LoginController::class, 'login'])->defaults('portal', 'office')->name('login.post');
    });

    Route::middleware(['auth', 'account.active', 'account.type:office'])->group(function () {
        Route::get('/dashboard', [OfficePortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/incidents', [OfficePortalController::class, 'incidents'])->name('incidents');
        Route::get('/incidents/{reference}', [OfficePortalController::class, 'showIncident'])->name('incidents.show');
        Route::post('/incidents/{reference}/review', [OfficePortalController::class, 'reviewIncident'])->name('incidents.review');
        Route::get('/map', [OfficePortalController::class, 'map'])->name('map');
        Route::get('/map/data', [OfficePortalController::class, 'mapData'])->name('map.data');
        Route::get('/records', [OfficePortalController::class, 'records'])->name('records');
        Route::get('/records/export', [OfficePortalController::class, 'exportReports'])->name('records.export');
        Route::get('/personnel', [OfficePortalController::class, 'personnel'])->name('personnel');
        Route::post('/personnel', [OfficePortalController::class, 'storePersonnel'])->name('personnel.store');
        Route::post('/staff', [OfficePortalController::class, 'storeStaff'])->name('staff.store');

        // Internal Messenger
        Route::get('/messages', [InternalMessengerController::class, 'inbox'])->name('messages');
        Route::get('/messages/{conversation}', [InternalMessengerController::class, 'show'])->name('messages.show');
        Route::post('/messages/{conversation}/send', [InternalMessengerController::class, 'storeMessage'])->name('messages.send');
        Route::get('/messages/direct/{userId}', [InternalMessengerController::class, 'startDirect'])->name('messages.direct');
        Route::get('/messages/incident/{reference}', [InternalMessengerController::class, 'startIncidentConversation'])->name('messages.incident');
        Route::post('/messages/{conversation}/read', [InternalMessengerController::class, 'markAsRead'])->name('messages.read');
    });
});

// 6. ADMIN — DIAL DAPH COMMAND CENTER ROUTES (Accessible by Command Center Admin & Super Admin)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [LoginController::class, 'showAdminLogin'])->name('login');
        Route::post('/login', [LoginController::class, 'login'])->defaults('portal', 'admin')->name('login.post');
    });

    Route::middleware(['auth', 'account.active', 'account.type:admin,super_admin'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
        Route::get('/dashboard/telemetry', [AdminCommandCenterController::class, 'telemetry'])->name('dashboard.telemetry');
        Route::get('/incoming', [AdminIncidentController::class, 'incoming'])->name('incoming.index');
        Route::get('/dispatch', [AdminCommandCenterController::class, 'dispatchConsole'])->name('dispatch.index');
        Route::get('/citizens', [AdminCommandCenterController::class, 'citizens'])->name('citizens.index');
        Route::post('/citizens/{id}/toggle-status', [AdminCommandCenterController::class, 'toggleCitizenStatus'])->name('citizens.toggle-status');
        Route::get('/users-monitor', [AdminCommandCenterController::class, 'usersMonitor'])->name('monitor.users');
        Route::get('/users-monitor/data', [AdminCommandCenterController::class, 'usersMonitorData'])->name('monitor.users.data');
        Route::get('/notifications', [AdminCommandCenterController::class, 'notifications'])->name('notifications.index');
        Route::post('/notifications/{id}/read', [AdminCommandCenterController::class, 'markNotificationRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [AdminCommandCenterController::class, 'markAllNotificationsRead'])->name('notifications.read-all');
        Route::get('/reports', [AdminCommandCenterController::class, 'reports'])->name('reports.index');
        Route::get('/reports/export', [AdminCommandCenterController::class, 'exportReports'])->name('reports.export');
        Route::get('/activity-logs', [AdminCommandCenterController::class, 'auditLogs'])->name('audit.index');
        Route::get('/eoc-settings', [AdminCommandCenterController::class, 'eocSettings'])->name('eoc-settings');
        Route::post('/eoc-settings', [AdminCommandCenterController::class, 'updateEocSettings'])->name('eoc-settings.update');
        Route::get('/system-settings', [AdminCommandCenterController::class, 'systemSettings'])->name('settings.index');
        Route::post('/system-settings', [AdminCommandCenterController::class, 'updateSystemSettings'])->name('settings.update');

        // Incidents & Call Verification / Emergency Assessment Workflow
        Route::get('/incidents', [AdminIncidentController::class, 'index'])->name('incidents.index');
        Route::get('/incidents/evidence', [AdminMediaController::class, 'evidenceViewer'])->name('incidents.evidence');
        Route::get('/incidents/{reference}', [AdminIncidentController::class, 'show'])->name('incidents.show');
        Route::post('/incidents/{reference}/call-attempt', [AdminIncidentController::class, 'initiateCall'])->name('incidents.call-attempt');
        Route::post('/incidents/{reference}/call-outcome', [AdminIncidentController::class, 'recordCallOutcome'])->name('incidents.call-outcome');
        Route::post('/incidents/{reference}/assess-deploy', [AdminIncidentController::class, 'assessAndDeploy'])->name('incidents.assess-deploy');
        Route::post('/incidents/{reference}/review', [AdminIncidentController::class, 'review'])->name('incidents.review');
        Route::post('/incidents/{reference}/override', [AdminIncidentController::class, 'override'])->name('incidents.override');
        Route::post('/incidents/{reference}/priority', [AdminIncidentController::class, 'updatePriority'])->name('incidents.priority');
        Route::post('/incidents/{reference}/assign', [AdminIncidentController::class, 'assign'])->name('incidents.assign');
        Route::post('/incidents/{reference}/dispatch', [AdminIncidentController::class, 'dispatch'])->name('incidents.dispatch');
        Route::post('/incidents/{reference}/verify-contact', [AdminIncidentController::class, 'verifyContact'])->name('incidents.verify-contact');
        Route::get('/incidents/{reference}/routing', [AdminIncidentController::class, 'routingRecommendation'])->name('incidents.routing');

        // SOS Alerts Handling
        Route::prefix('sos')->name('sos.')->group(function () {
            Route::get('/', [AdminSosController::class, 'index'])->name('index');
            Route::get('/{id}', [AdminSosController::class, 'show'])->name('show');
            Route::post('/{id}/acknowledge', [AdminSosController::class, 'acknowledge'])->name('acknowledge');
            Route::post('/{id}/call-attempt', [AdminSosController::class, 'callAttempt'])->name('call-attempt');
            Route::post('/{id}/assign', [AdminSosController::class, 'assign'])->name('assign');
            Route::post('/{id}/escalate', [AdminSosController::class, 'escalate'])->name('escalate');
            Route::post('/{id}/resolve', [AdminSosController::class, 'resolve'])->name('resolve');
            Route::post('/{id}/convert', [AdminSosController::class, 'convert'])->name('convert');
        });

        // Live Operations Map Routes
        Route::get('/map', [AdminLiveOperationsMapController::class, 'index'])->name('map');
        Route::get('/map/data', [AdminLiveOperationsMapController::class, 'data'])->name('map.data');
        Route::get('/map/boundaries', [AdminLiveOperationsMapController::class, 'boundaries'])->name('map.boundaries');
        Route::post('/map/boundaries/import', [AdminLiveOperationsMapController::class, 'importBoundaries'])->name('map.boundaries.import');

        // Personnel Management Routes
        Route::prefix('personnel')->name('personnel.')->group(function () {
            Route::get('/', [AdminPersonnelController::class, 'index'])->name('index');
            Route::get('/create', [AdminPersonnelController::class, 'create'])->name('create');
            Route::post('/', [AdminPersonnelController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AdminPersonnelController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminPersonnelController::class, 'update'])->name('update');
            Route::post('/{id}/toggle-status', [AdminPersonnelController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{id}/reset-password', [AdminPersonnelController::class, 'resetPassword'])->name('reset-password');
            Route::get('/teams', [AdminPersonnelController::class, 'teams'])->name('teams');
            Route::post('/teams', [AdminPersonnelController::class, 'storeTeam'])->name('teams.store');
            Route::put('/teams/{id}', [AdminPersonnelController::class, 'updateTeam'])->name('teams.update');
            Route::get('/jurisdictions', [AdminPersonnelController::class, 'jurisdictions'])->name('jurisdictions');
            Route::get('/availability', [AdminPersonnelController::class, 'availability'])->name('availability');
            Route::get('/assignments', [AdminPersonnelController::class, 'assignments'])->name('assignments');
            Route::get('/bases', [AdminPersonnelController::class, 'bases'])->name('bases');
            Route::post('/bases', [AdminPersonnelController::class, 'storeBase'])->name('bases.store');
        });

        // Office Management Routes
        Route::prefix('offices')->name('offices.')->group(function () {
            Route::get('/', [AdminOfficeController::class, 'index'])->name('index');
            Route::get('/create', [AdminOfficeController::class, 'create'])->name('create');
            Route::post('/', [AdminOfficeController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AdminOfficeController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminOfficeController::class, 'update'])->name('update');
            Route::post('/{id}/toggle-status', [AdminOfficeController::class, 'toggleStatus'])->name('toggle-status');
            Route::get('/{id}/users', [AdminOfficeController::class, 'users'])->name('users');
            Route::post('/{id}/users', [AdminOfficeController::class, 'storeUser'])->name('users.store');
            Route::put('/{officeId}/users/{userId}', [AdminOfficeController::class, 'updateUser'])->name('users.update');
            Route::post('/{officeId}/users/{userId}/toggle-status', [AdminOfficeController::class, 'toggleUserStatus'])->name('users.toggle-status');
            Route::post('/{officeId}/users/{userId}/reset-password', [AdminOfficeController::class, 'resetUserPassword'])->name('users.reset-password');
        });

        // Internal Messenger Routes
        Route::prefix('messages')->name('messages.')->group(function () {
            Route::get('/', [InternalMessengerController::class, 'inbox'])->name('index');
            Route::get('/{conversation}', [InternalMessengerController::class, 'show'])->name('show');
            Route::post('/{conversation}/send', [InternalMessengerController::class, 'storeMessage'])->name('send');
            Route::post('/group', [InternalMessengerController::class, 'createGroup'])->name('group');
            Route::get('/direct/{userId}', [InternalMessengerController::class, 'startDirect'])->name('direct');
            Route::get('/incident/{reference}', [InternalMessengerController::class, 'startIncidentConversation'])->name('incident');
            Route::post('/{conversation}/read', [InternalMessengerController::class, 'markAsRead'])->name('read');
            Route::post('/{conversation}/archive', [InternalMessengerController::class, 'archive'])->name('archive');
        });

        // Facebook Messenger Communications
        Route::prefix('communications/facebook-messenger')->name('communications.fb.')->group(function () {
            Route::get('/', [AdminFacebookMessengerController::class, 'inbox'])->name('inbox');
            Route::get('/settings', [AdminFacebookMessengerController::class, 'settings'])->name('settings');
            Route::post('/settings', [AdminFacebookMessengerController::class, 'updateSettings'])->name('settings.update');
            Route::get('/{conversation}', [AdminFacebookMessengerController::class, 'show'])->name('show');
            Route::post('/{conversation}/reply', [AdminFacebookMessengerController::class, 'reply'])->name('reply');
            Route::post('/intakes/{intake}/convert', [AdminFacebookMessengerController::class, 'convertIntake'])->name('intakes.convert');
            Route::post('/intakes/{intake}/reject', [AdminFacebookMessengerController::class, 'rejectIntake'])->name('intakes.reject');
        });

        // Centralized Media & Evidence Management
        Route::prefix('media')->name('media.')->group(function () {
            Route::get('/', [AdminMediaController::class, 'index'])->name('index');
            Route::post('/settings', [AdminMediaController::class, 'updateSettings'])->name('settings');
            Route::get('/{media}', [AdminMediaController::class, 'show'])->name('show');
            Route::post('/{media}/archive', [AdminMediaController::class, 'archive'])->name('archive');
            Route::post('/{media}/retry', [AdminMediaController::class, 'retryProcessing'])->name('retry');
            Route::post('/{media}/confirm-location', [AdminMediaController::class, 'confirmLocation'])->name('confirm-location');
            Route::delete('/{media}/purge', [AdminMediaController::class, 'purge'])->name('purge');
        });
    });
});

// 7. LDRRMO SUPER ADMIN ROUTES (Strictly locked to account_type = super_admin)
Route::prefix('super-admin')->name('super-admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [LoginController::class, 'showSuperAdminLogin'])->name('login');
        Route::post('/login', [LoginController::class, 'login'])->defaults('portal', 'super_admin')->name('login.post');
    });

    Route::middleware(['auth', 'account.active', 'account.type:super_admin'])->group(function () {
        Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/monitoring', [SuperAdminController::class, 'monitoring'])->name('monitoring');
        Route::get('/incoming', [AdminIncidentController::class, 'incoming'])->name('incoming.index');
        Route::get('/sos', [AdminSosController::class, 'index'])->name('sos.index');
        Route::get('/sos/{id}', [AdminSosController::class, 'show'])->name('sos.show');
        Route::get('/incidents', [AdminIncidentController::class, 'index'])->name('incidents.index');
        Route::get('/incidents/{reference}', [AdminIncidentController::class, 'show'])->name('incidents.show');
        Route::get('/map', [AdminLiveOperationsMapController::class, 'index'])->name('map');
        Route::get('/offices', [AdminOfficeController::class, 'index'])->name('offices.index');
        Route::get('/personnel', [AdminPersonnelController::class, 'index'])->name('personnel.index');
        Route::get('/citizens', [AdminCommandCenterController::class, 'citizens'])->name('citizens.index');
        Route::get('/messages', [InternalMessengerController::class, 'inbox'])->name('messages.index');
        Route::get('/reports', [AdminCommandCenterController::class, 'reports'])->name('reports.index');
        Route::get('/eoc-settings', [AdminCommandCenterController::class, 'eocSettings'])->name('eoc-settings');
        Route::get('/system-settings', [AdminCommandCenterController::class, 'systemSettings'])->name('settings.index');
        Route::get('/audit-logs', [AdminCommandCenterController::class, 'auditLogs'])->name('audit.index');

        // Super Admin Exclusive: Admin Accounts & Permissions
        Route::get('/admins', [SuperAdminController::class, 'adminsIndex'])->name('admins.index');
        Route::post('/admins', [SuperAdminController::class, 'storeAdmin'])->name('admins.store');
        Route::put('/admins/{id}', [SuperAdminController::class, 'updateAdmin'])->name('admins.update');
        Route::post('/admins/invitations', [SuperAdminController::class, 'storeAdminInvitation'])->name('admins.invite');

        // Super Admin Exclusive: Routing & Category Configuration
        Route::get('/routing-rules', [SuperAdminController::class, 'routingIndex'])->name('routing.index');
        Route::post('/routing-rules/incidents', [SuperAdminController::class, 'storeIncidentRoutingRule'])->name('routing.incidents.store');
        Route::delete('/routing-rules/incidents/{id}', [SuperAdminController::class, 'deleteIncidentRoutingRule'])->name('routing.incidents.delete');
        Route::post('/routing-rules/sos', [SuperAdminController::class, 'storeSosRoutingRule'])->name('routing.sos.store');
        Route::delete('/routing-rules/sos/{id}', [SuperAdminController::class, 'deleteSosRoutingRule'])->name('routing.sos.delete');
        Route::post('/categories', [SuperAdminController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{id}', [SuperAdminController::class, 'updateCategory'])->name('categories.update');

        // Super Admin Exclusive: Backups & Restore
        Route::get('/backups', [SuperAdminController::class, 'backupsIndex'])->name('backups.index');
        Route::post('/backups', [SuperAdminController::class, 'createBackup'])->name('backups.store');
        Route::get('/backups/{id}/download', [SuperAdminController::class, 'downloadBackup'])->name('backups.download');
        Route::post('/backups/{id}/restore', [SuperAdminController::class, 'restoreBackup'])->name('backups.restore');
    });
});

// 8. COMMON AUTHENTICATED MAPPING, MEDIA & NAVIGATION ROUTES
Route::middleware(['auth', 'account.active'])->group(function () {
    Route::match(['get', 'post'], '/map/route', [RouteNavigationController::class, 'calculate'])->name('map.route');
    Route::get('/messenger/attachments/{id}/download', [InternalMessengerController::class, 'downloadAttachment'])
        ->name('messenger.attachments.download');

    // Authorized Private Media Streaming & Downloads
    Route::get('/media/{media}/stream', [MediaController::class, 'stream'])->name('media.stream');
    Route::get('/media/{media}/thumbnail', [MediaController::class, 'thumbnail'])->name('media.thumbnail');
    Route::get('/media/{media}/download', [MediaController::class, 'download'])->name('media.download');
});

// 9. META FACEBOOK MESSENGER WEBHOOKS
Route::prefix('webhooks/facebook/messenger')->group(function () {
    Route::get('/', [FacebookMessengerWebhookController::class, 'verify'])->name('webhooks.facebook.verify');
    Route::post('/', [FacebookMessengerWebhookController::class, 'handle'])->name('webhooks.facebook.handle');
});
