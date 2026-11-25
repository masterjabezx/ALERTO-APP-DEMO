<?php

declare(strict_types=1);

namespace App\Http\Controllers\Messenger;

use App\Http\Controllers\Controller;
use App\Http\Requests\Messenger\CreateGroupConversationRequest;
use App\Http\Requests\Messenger\SendMessageRequest;
use App\Models\Conversation;
use App\Models\Incident;
use App\Models\MediaAttachment;
use App\Models\User;
use App\Services\InternalMessengerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InternalMessengerController extends Controller
{
    public function __construct(
        protected InternalMessengerService $messengerService
    ) {}

    /**
     * Show messenger inbox with active conversation or list conversations.
     */
    public function inbox(Request $request): View|JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->isCitizen()) {
            abort(403, 'Citizens do not have access to Internal Messenger.');
        }

        $search = $request->query('search');
        $conversations = $this->messengerService->getConversationsForUser($user, is_string($search) ? $search : null);

        $selectedConversationId = $request->query('conversation_id');
        $activeConversation = null;
        $activeMessages = collect();

        if ($selectedConversationId) {
            $candidate = Conversation::with(['participants.user', 'incident.category'])->find($selectedConversationId);
            if ($candidate && $this->messengerService->canAccessConversation($user, $candidate)) {
                $activeConversation = $candidate;
            }
        }

        if (! $activeConversation && $conversations->isNotEmpty()) {
            $activeConversation = $conversations->first();
        }

        if ($activeConversation) {
            $this->messengerService->markConversationAsRead($user, $activeConversation);

            $activeMessages = $activeConversation->messages()
                ->with([
                    'sender.personnelProfile',
                    'sender.officeUsers.office',
                    'attachments.geoMetadata',
                    'replyTo.sender',
                    'incident.category',
                ])
                ->oldest('id')
                ->get();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'conversations' => $conversations,
                'active_conversation' => $activeConversation,
                'messages' => $activeMessages,
            ]);
        }

        // Available contacts for direct chat
        $availableContacts = collect();
        if ($user->isAdmin()) {
            $availableContacts = User::whereIn('account_type', ['personnel', 'office', 'admin'])
                ->where('id', '!=', $user->id)
                ->where('is_active', true)
                ->with(['personnelProfile', 'offices'])
                ->get();
        } elseif ($user->isPersonnel()) {
            $availableContacts = User::where('account_type', 'admin')
                ->where('is_active', true)
                ->get();
        } elseif ($user->isOffice()) {
            $officeIds = $user->offices()->pluck('offices.id');
            $availableContacts = User::where(function ($q) use ($officeIds) {
                $q->where('account_type', 'admin')
                    ->orWhere(function ($oq) use ($officeIds) {
                        $oq->where('account_type', 'office')
                            ->whereHas('offices', fn ($o) => $o->whereIn('offices.id', $officeIds));
                    });
            })
                ->where('id', '!=', $user->id)
                ->where('is_active', true)
                ->with('offices')
                ->get();
        }

        $viewData = [
            'user' => $user,
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
            'messages' => $activeMessages,
            'availableContacts' => $availableContacts,
            'search' => $search,
        ];

        return match ($user->account_type) {
            'admin' => view('admin.messages.index', $viewData),
            'office' => view('office.messages', array_merge($viewData, [
                'office' => $user->primaryOffice(),
                'adminUser' => User::where('account_type', 'admin')->first(),
            ])),
            default => view('personnel.messages', $viewData),
        };
    }

    /**
     * Show a single conversation's messages (AJAX or direct route).
     */
    public function show(Request $request, Conversation $conversation): View|JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $this->messengerService->canAccessConversation($user, $conversation)) {
            abort(403, 'Unauthorized access to this conversation.');
        }

        $this->messengerService->markConversationAsRead($user, $conversation);

        $messages = $conversation->messages()
            ->with([
                'sender.personnelProfile',
                'sender.officeUsers.office',
                'attachments.geoMetadata',
                'replyTo.sender',
                'incident.category',
            ])
            ->oldest('id')
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'conversation' => $conversation->load(['participants.user', 'incident.category']),
                'messages' => $messages,
            ]);
        }

        $prefix = match ($user->account_type) {
            'admin' => 'admin.messages.index',
            'office' => 'office.messages',
            default => 'personnel.messages',
        };

        return redirect()->route($prefix, ['conversation_id' => $conversation->id]);
    }

    /**
     * Store and send a new message in an active conversation.
     */
    public function storeMessage(SendMessageRequest $request, Conversation $conversation): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $this->messengerService->canAccessConversation($user, $conversation)) {
            abort(403, 'You are not a participant in this conversation.');
        }

        $validated = $request->validated();
        $files = $request->file('attachments');
        $fileOrFiles = ! empty($files) ? $files : $request->file('attachment');

        $geoData = [];
        if ($request->filled('latitude') && $request->filled('longitude')) {
            $geoData = [
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'accuracy' => $request->input('accuracy'),
                'gps_source' => $request->input('gps_source', 'device_live_upload'),
                'is_live_capture' => $request->boolean('is_live_capture', false),
            ];
        } elseif ($request->filled('gps_source')) {
            $geoData = ['gps_source' => $request->input('gps_source')];
        }

        $message = $this->messengerService->sendMessage(
            $user,
            $conversation,
            $validated,
            $fileOrFiles,
            $geoData
        );

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $message->load([
                    'sender.personnelProfile',
                    'sender.officeUsers.office',
                    'attachments.geoMetadata',
                    'replyTo.sender',
                    'incident.category',
                ]),
            ], 201);
        }

        return back()->with('success', 'Message sent successfully.');
    }

    /**
     * Start or open a direct conversation with a specific user.
     */
    public function startDirect(Request $request, int $userId): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $targetUser = User::findOrFail($userId);

        $conversation = $this->messengerService->getOrCreateDirectConversation($user, $targetUser);

        $routeName = match ($user->account_type) {
            'admin' => 'admin.messages.index',
            'office' => 'office.messages',
            default => 'personnel.messages',
        };

        return redirect()->route($routeName, ['conversation_id' => $conversation->id]);
    }

    /**
     * Start or open an incident-linked conversation ("MESSAGE ADMIN ABOUT THIS INCIDENT").
     */
    public function startIncidentConversation(Request $request, string $reference): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $incident = Incident::where('reference_number', $reference)->firstOrFail();

        $conversation = $this->messengerService->getOrCreateIncidentConversation($user, $incident);

        $routeName = match ($user->account_type) {
            'admin' => 'admin.messages.index',
            'office' => 'office.messages',
            default => 'personnel.messages',
        };

        return redirect()->route($routeName, ['conversation_id' => $conversation->id]);
    }

    /**
     * Create a new group conversation (Admin only).
     */
    public function createGroup(CreateGroupConversationRequest $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validated();
        $conversation = $this->messengerService->createGroupConversation(
            $user,
            $validated['title'],
            $validated['participant_ids']
        );

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'conversation' => $conversation->load('participants.user'),
            ], 201);
        }

        return redirect()->route('admin.messages.index', ['conversation_id' => $conversation->id])
            ->with('success', 'Group conversation initialized.');
    }

    /**
     * Mark conversation messages as read.
     */
    public function markAsRead(Request $request, Conversation $conversation): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $this->messengerService->canAccessConversation($user, $conversation)) {
            abort(403, 'Unauthorized access to this conversation.');
        }

        $this->messengerService->markConversationAsRead($user, $conversation);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Archive or unarchive a conversation.
     */
    public function archive(Request $request, Conversation $conversation): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $this->messengerService->canAccessConversation($user, $conversation)) {
            abort(403, 'Unauthorized.');
        }

        $archive = $request->boolean('archive', true);
        $this->messengerService->archiveConversation($conversation, $archive);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'is_archived' => $archive]);
        }

        return back()->with('success', $archive ? 'Conversation archived.' : 'Conversation unarchived.');
    }

    /**
     * Download an attachment securely from private storage.
     */
    public function downloadAttachment(int $id): StreamedResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $media = MediaAttachment::findOrFail($id);

        if (! $this->messengerService->canDownloadAttachment($user, $media)) {
            abort(403, 'You are not authorized to download this file.');
        }

        if (! Storage::disk($media->disk)->exists($media->file_path)) {
            abort(404, 'File not found on storage.');
        }

        return Storage::disk($media->disk)->download($media->file_path, $media->file_name);
    }
}
