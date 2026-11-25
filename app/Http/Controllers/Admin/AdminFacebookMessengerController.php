<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConvertFbIntakeRequest;
use App\Http\Requests\Admin\SendFbReplyRequest;
use App\Http\Requests\Admin\UpdateFbSettingsRequest;
use App\Models\Barangay;
use App\Models\FbConversation;
use App\Models\FbIntegrationSetting;
use App\Models\FbMessage;
use App\Models\IncidentCategory;
use App\Models\IncidentIntakeRecord;
use App\Models\User;
use App\Services\FacebookMessengerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminFacebookMessengerController extends Controller
{
    public function __construct(
        protected FacebookMessengerService $messengerService
    ) {}

    /**
     * Show Facebook Messenger 3-panel Admin Inbox.
     */
    public function inbox(Request $request): View|JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $search = $request->query('search');
        $statusFilter = $request->query('status', 'all');

        $conversations = $this->messengerService->getConversations(
            is_string($search) ? $search : null,
            is_string($statusFilter) ? $statusFilter : null
        );

        $selectedId = $request->query('conversation_id');
        $activeConversation = null;
        $activeMessages = collect();

        if ($selectedId) {
            $activeConversation = FbConversation::with([
                'messages.attachments.media',
                'intakeRecord.category',
                'intakeRecord.barangay',
                'intakeRecord.incident',
            ])->find($selectedId);
        }

        if (! $activeConversation && $conversations->isNotEmpty()) {
            $activeConversation = $conversations->first();
        }

        if ($activeConversation) {
            // Mark conversation as read
            $activeConversation->update(['unread_count' => 0]);

            $activeMessages = $activeConversation->messages()
                ->with(['attachments.media.geoMetadata'])
                ->oldest('sent_at')
                ->get();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'conversations' => $conversations,
                'active_conversation' => $activeConversation,
                'messages' => $activeMessages,
            ]);
        }

        $categories = IncidentCategory::orderBy('name')->get();
        $barangays = Barangay::orderBy('name')->get();

        return view('admin.communications.facebook-messenger.inbox', [
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
            'messages' => $activeMessages,
            'categories' => $categories,
            'barangays' => $barangays,
            'search' => $search,
            'statusFilter' => $statusFilter,
        ]);
    }

    /**
     * Show conversation JSON or redirect.
     */
    public function show(Request $request, FbConversation $conversation): View|JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $conversation->update(['unread_count' => 0]);

        if ($request->wantsJson()) {
            return response()->json([
                'conversation' => $conversation->load('intakeRecord.category', 'intakeRecord.barangay'),
                'messages' => $conversation->messages()->with('attachments.media.geoMetadata')->oldest('sent_at')->get(),
            ]);
        }

        return redirect()->route('admin.communications.fb.inbox', ['conversation_id' => $conversation->id]);
    }

    /**
     * Send outbound reply from Admin to citizen on Facebook Messenger.
     */
    public function reply(SendFbReplyRequest $request, FbConversation $conversation): JsonResponse|RedirectResponse
    {
        /** @var User $admin */
        $admin = Auth::user();

        $message = $this->messengerService->sendAdminReply(
            $conversation,
            $request->validated('message_text'),
            $admin
        );

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'delivery_status' => $message->delivery_status,
                'message' => $message,
                'error' => $message->error_message,
            ], $message->delivery_status === 'failed' ? 422 : 201);
        }

        if ($message->delivery_status === 'failed') {
            return back()->with('error', 'Failed sending reply through Meta API: '.($message->error_message ?? 'Unknown error'));
        }

        return back()->with('success', 'Reply dispatched to citizen on Facebook Messenger.');
    }

    /**
     * Convert an Incident Intake into a formal Incident record.
     */
    public function convertIntake(ConvertFbIntakeRequest $request, IncidentIntakeRecord $intake): RedirectResponse
    {
        /** @var User $admin */
        $admin = Auth::user();

        $incident = $this->messengerService->convertIntakeToIncident(
            $intake,
            $admin,
            $request->validated()
        );

        return redirect()->route('admin.incidents.show', $incident->reference_number)
            ->with('success', "Intake successfully converted into Incident #{$incident->reference_number} and placed in the Pending Review queue.");
    }

    /**
     * Reject an Incident Intake with reason.
     */
    public function rejectIntake(Request $request, IncidentIntakeRecord $intake): RedirectResponse
    {
        /** @var User $admin */
        $admin = Auth::user();

        $reason = $request->input('rejection_reason', 'Report rejected after administrative assessment.');

        $intake->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Incident intake rejected.');
    }

    /**
     * Facebook Messenger Integration Settings Dashboard.
     */
    public function settings(): View
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $setting = FbIntegrationSetting::firstOrCreate(
            ['page_id' => config('services.meta.page_id') ?: '100234567890'],
            [
                'page_name' => config('services.meta.page_name') ?: 'Hinoba-an LDRRMO Emergency Office',
                'app_id' => config('services.meta.app_id') ?: '5432167890',
                'verify_token' => config('services.meta.verify_token') ?: 'alerto_hinobaan_webhook_token',
                'is_active' => true,
                'webhook_verified' => false,
            ]
        );

        $appId = config('services.meta.app_id') ?: $setting->app_id;
        $maskedAppSecret = ! empty(config('services.meta.app_secret'))
            ? Str::mask(config('services.meta.app_secret'), '*', 4, -4)
            : 'Not configured';

        $maskedPageToken = ! empty(config('services.meta.page_access_token'))
            ? Str::mask(config('services.meta.page_access_token'), '*', 6, -6)
            : 'Not configured';

        $totalConversations = FbConversation::count();
        $totalMessages = FbMessage::count();
        $totalIntakes = IncidentIntakeRecord::count();

        return view('admin.communications.facebook-messenger.settings', [
            'setting' => $setting,
            'appId' => $appId,
            'maskedAppSecret' => $maskedAppSecret,
            'maskedPageToken' => $maskedPageToken,
            'graphVersion' => config('services.meta.graph_version', 'v22.0'),
            'webhookUrl' => url('/webhooks/facebook/messenger'),
            'totalConversations' => $totalConversations,
            'totalMessages' => $totalMessages,
            'totalIntakes' => $totalIntakes,
        ]);
    }

    /**
     * Update Facebook integration configuration.
     */
    public function updateSettings(UpdateFbSettingsRequest $request): RedirectResponse
    {
        $setting = FbIntegrationSetting::first();
        if (! $setting) {
            $setting = new FbIntegrationSetting;
        }

        $setting->fill($request->validated());
        $setting->is_active = $request->boolean('is_active', true);
        $setting->save();

        return back()->with('success', 'Facebook Messenger integration settings updated successfully.');
    }
}
