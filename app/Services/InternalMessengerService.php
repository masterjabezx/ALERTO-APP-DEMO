<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\ConversationUpdated;
use App\Events\InternalMessageSent;
use App\Events\MessageRead;
use App\Models\Conversation;
use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\IncidentRoutingRule;
use App\Models\MediaAttachment;
use App\Models\Message;
use App\Models\MessageReadReceipt;
use App\Models\OfficeResponsibility;
use App\Models\ResponseTeam;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class InternalMessengerService
{
    /**
     * Get accessible conversations for a user.
     *
     * @return Collection<int, Conversation>
     */
    public function getConversationsForUser(User $user, ?string $search = null, bool $includeArchived = false): Collection
    {
        if ($user->isCitizen()) {
            throw new AccessDeniedHttpException('Citizens do not have access to Internal Messenger.');
        }

        $query = Conversation::query()
            ->with([
                'latestMessage.sender.personnelProfile',
                'latestMessage.sender.officeUsers.office',
                'participants.user.personnelProfile',
                'participants.user.officeUsers.office',
                'incident.category',
            ]);

        if (! $includeArchived) {
            $query->where('is_archived', false);
        }

        if (! $user->isAdmin()) {
            $query->whereHas('participants', fn ($q) => $q->where('user_id', $user->id));
        }

        if ($search !== null && trim($search) !== '') {
            $term = trim($search);
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhereHas('participants.user', fn ($u) => $u->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('incident', fn ($i) => $i->where('reference_number', 'like', "%{$term}%"));
            });
        }

        return $query->orderByDesc('updated_at')->get();
    }

    /**
     * Get or create a direct conversation between two users according to role permissions.
     */
    public function getOrCreateDirectConversation(User $user1, User $user2): Conversation
    {
        if ($user1->isCitizen() || $user2->isCitizen()) {
            throw new AccessDeniedHttpException('Citizens do not have access to Internal Messenger.');
        }

        // Authorization rules:
        // 1. Admin can message anyone (Personnel, Office, Admin).
        // 2. Personnel can message Admin.
        // 3. Office can message Admin.
        // 4. Office members can message other members of the SAME office.
        $isPermitted = false;

        if ($user1->isAdmin() || $user2->isAdmin()) {
            $isPermitted = true;
        } elseif ($user1->isOffice() && $user2->isOffice()) {
            $commonOffices = $user1->offices()->pluck('offices.id')
                ->intersect($user2->offices()->pluck('offices.id'));
            $isPermitted = $commonOffices->isNotEmpty();
        }

        if (! $isPermitted) {
            throw new AccessDeniedHttpException('Direct messaging between these account roles is not permitted.');
        }

        // Check if direct conversation already exists
        $conversation = Conversation::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user1->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user2->id))
            ->whereDoesntHave('participants', fn ($q) => $q->whereNotIn('user_id', [$user1->id, $user2->id]))
            ->first();

        if ($conversation) {
            return $conversation;
        }

        $conversation = Conversation::create([
            'type' => 'direct',
            'title' => null,
            'created_by' => $user1->id,
            'is_archived' => false,
        ]);

        $conversation->participants()->createMany([
            [
                'user_id' => $user1->id,
                'is_admin' => $user1->isAdmin(),
                'joined_at' => now(),
            ],
            [
                'user_id' => $user2->id,
                'is_admin' => $user2->isAdmin(),
                'joined_at' => now(),
            ],
        ]);

        return $conversation;
    }

    /**
     * Get or create an incident-linked conversation.
     */
    public function getOrCreateIncidentConversation(User $creator, Incident $incident): Conversation
    {
        if ($creator->isCitizen()) {
            throw new AccessDeniedHttpException('Citizens do not have access to Internal Messenger.');
        }

        // Enforce jurisdiction / assignment / office scope for non-Admin users
        if (! $creator->isAdmin()) {
            $authorized = false;

            if ($creator->isOffice()) {
                $officeIds = $creator->offices()->pluck('offices.id');
                $teamIds = ResponseTeam::whereIn('office_id', $officeIds)->pluck('id');
                $categorySlugs = OfficeResponsibility::whereIn('office_id', $officeIds)->pluck('category_slug');

                $authorized = $creator->canReviewIncident($incident)
                    || IncidentAssignment::where('incident_id', $incident->id)
                        ->where(function ($q) use ($officeIds, $teamIds): void {
                            $q->whereIn('office_id', $officeIds)
                                ->orWhereIn('team_id', $teamIds);
                        })
                        ->exists()
                    || ($incident->category && $categorySlugs->contains($incident->category->slug))
                    || IncidentRoutingRule::where('category_id', $incident->category_id)
                        ->whereIn('target_office_id', $officeIds)
                        ->exists();
            } elseif ($creator->isPersonnel()) {
                $personnel = $creator->personnelProfile;
                if ($personnel) {
                    $teamIds = $personnel->teams()->pluck('response_teams.id');
                    $isAssigned = IncidentAssignment::where('incident_id', $incident->id)
                        ->where(function ($q) use ($personnel, $teamIds): void {
                            $q->where('personnel_id', $personnel->id)
                                ->orWhereIn('team_id', $teamIds);
                        })
                        ->exists();

                    $jurisdictionBarangays = $personnel->jurisdictions()->pluck('barangay_id');
                    $isActiveIncident = in_array($incident->status, ['accepted', 'dispatched', 'acknowledged', 'on_the_way', 'on_scene', 'in_progress'], true);
                    $inJurisdiction = $isActiveIncident && ($jurisdictionBarangays->isEmpty() || $jurisdictionBarangays->contains($incident->barangay_id));

                    $authorized = $isAssigned || $inJurisdiction;
                }
            }

            if (! $authorized) {
                throw new AccessDeniedHttpException('You are not authorized to open a command channel for this incident.');
            }
        }

        $conversation = Conversation::where('type', 'incident')
            ->where('incident_id', $incident->id)
            ->first();

        if ($conversation) {
            if (! $conversation->participants()->where('user_id', $creator->id)->exists()) {
                $conversation->participants()->create([
                    'user_id' => $creator->id,
                    'is_admin' => $creator->isAdmin(),
                    'joined_at' => now(),
                ]);
            }

            return $conversation;
        }

        $title = "Incident #{$incident->reference_number} - ".($incident->category?->name ?? 'Coordination');

        $conversation = Conversation::create([
            'type' => 'incident',
            'incident_id' => $incident->id,
            'title' => $title,
            'created_by' => $creator->id,
            'is_archived' => false,
        ]);

        // Add creator
        $conversation->participants()->create([
            'user_id' => $creator->id,
            'is_admin' => $creator->isAdmin(),
            'joined_at' => now(),
        ]);

        // Add all active Admin users to ensure LDRRMO Command is notified
        $admins = User::where('account_type', 'admin')
            ->where('id', '!=', $creator->id)
            ->get();

        foreach ($admins as $admin) {
            $conversation->participants()->create([
                'user_id' => $admin->id,
                'is_admin' => true,
                'joined_at' => now(),
            ]);
        }

        return $conversation;
    }

    /**
     * Create a group conversation (Admin only).
     *
     * @param  array<int, int>  $participantUserIds
     */
    public function createGroupConversation(User $creator, string $title, array $participantUserIds): Conversation
    {
        if (! $creator->isAdmin()) {
            throw new AccessDeniedHttpException('Only Admin accounts can create group conversations.');
        }

        $validParticipants = User::whereIn('id', $participantUserIds)
            ->where('account_type', '!=', 'citizen')
            ->pluck('id')
            ->toArray();

        if (empty($validParticipants)) {
            throw new \InvalidArgumentException('At least one valid personnel or office participant is required.');
        }

        $conversation = Conversation::create([
            'type' => 'group',
            'title' => $title,
            'created_by' => $creator->id,
            'is_archived' => false,
        ]);

        $conversation->participants()->create([
            'user_id' => $creator->id,
            'is_admin' => true,
            'joined_at' => now(),
        ]);

        foreach (array_unique($validParticipants) as $userId) {
            if ($userId !== $creator->id) {
                $conversation->participants()->create([
                    'user_id' => $userId,
                    'is_admin' => false,
                    'joined_at' => now(),
                ]);
            }
        }

        return $conversation;
    }

    /**
     * Send a message within a conversation with optional single or multiple media attachments and optional GPS geo-tagging.
     *
     * @param  array<string, mixed>  $data
     * @param  UploadedFile|array<int, UploadedFile>|null  $fileOrFiles
     * @param  array<string, mixed>  $geoData
     */
    public function sendMessage(
        User $sender,
        Conversation $conversation,
        array $data,
        UploadedFile|array|null $fileOrFiles = null,
        array $geoData = []
    ): Message {
        if (! $this->canAccessConversation($sender, $conversation)) {
            throw new AccessDeniedHttpException('You are not authorized to send messages in this conversation.');
        }

        $files = [];
        if ($fileOrFiles instanceof UploadedFile) {
            $files = [$fileOrFiles];
        } elseif (is_array($fileOrFiles)) {
            $files = array_values(array_filter($fileOrFiles, static fn ($f) => $f instanceof UploadedFile));
        }

        $messageType = 'text';
        $incidentId = $data['incident_id'] ?? $conversation->incident_id;

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'incident_id' => $incidentId,
            'reply_to_message_id' => $data['reply_to_message_id'] ?? null,
            'body' => $data['body'] ?? null,
            'message_type' => 'text',
        ]);

        if (! empty($files)) {
            $mediaService = app(MediaManagementService::class);
            $sourceChannel = 'internal_messenger_'.$sender->account_type;
            $allowInherit = ! empty($data['inherit_incident_location']);

            $geoContext = [
                'latitude' => $geoData['latitude'] ?? null,
                'longitude' => $geoData['longitude'] ?? null,
                'accuracy' => $geoData['accuracy'] ?? null,
                'location_source' => $geoData['gps_source'] ?? null,
                'is_live_capture' => ! empty($geoData['is_live_capture']),
                'allow_incident_inherit' => $allowInherit,
            ];

            $resolvedIncident = $incidentId ? Incident::with('location')->find($incidentId) : null;
            $firstType = null;

            foreach ($files as $uploaded) {
                $evidenceType = match ($sender->account_type) {
                    'personnel' => 'responder_on_scene',
                    'office' => 'damage_assessment',
                    default => 'citizen_submission',
                };

                $media = $mediaService->storeUploadedFile($uploaded, $sender, [
                    'disk' => 'private',
                    'source_channel' => $sourceChannel,
                    'allowed_categories' => ['photo', 'video', 'document'],
                    'attachable' => $message,
                    'incident_id' => $resolvedIncident?->id,
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id,
                    'link_as_evidence' => $resolvedIncident !== null && in_array($uploaded->getClientOriginalExtension(), ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'webm', 'mov'], true),
                    'evidence_type' => $evidenceType,
                    'caption' => $data['body'] ?? "Shared in conversation #{$conversation->id} by {$sender->name}.",
                    'geo_context' => $geoContext,
                ]);

                $message->attachments()->attach($media->id);

                if ($firstType === null) {
                    $firstType = match ($media->media_type) {
                        'photo' => 'image',
                        'video' => 'video',
                        default => 'document',
                    };
                }
            }

            if ($firstType !== null) {
                $message->update(['message_type' => $firstType]);
                $messageType = $firstType;
            }
        }

        // Mark as read for sender
        $conversation->participants()
            ->where('user_id', $sender->id)
            ->update(['last_read_message_id' => $message->id]);

        MessageReadReceipt::firstOrCreate(
            ['message_id' => $message->id, 'user_id' => $sender->id],
            ['read_at' => now()]
        );

        $conversation->touch();

        // Broadcast events
        $message->load([
            'sender.personnelProfile',
            'sender.officeUsers.office',
            'attachments.geoMetadata',
            'incident.category',
        ]);

        InternalMessageSent::dispatch($message);
        ConversationUpdated::dispatch($conversation, $message->body ?? ($messageType.' attachment'));

        return $message;
    }

    /**
     * Mark a conversation as read by the user.
     */
    public function markConversationAsRead(User $user, Conversation $conversation): void
    {
        if (! $this->canAccessConversation($user, $conversation)) {
            return;
        }

        $latestMessage = $conversation->messages()->latest('id')->first();
        if (! $latestMessage) {
            return;
        }

        $conversation->participants()
            ->where('user_id', $user->id)
            ->update(['last_read_message_id' => $latestMessage->id]);

        // Create read receipt for messages not yet marked
        $unreadMessages = $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('readReceipts', fn ($q) => $q->where('user_id', $user->id))
            ->get();

        foreach ($unreadMessages as $msg) {
            MessageReadReceipt::firstOrCreate(
                ['message_id' => $msg->id, 'user_id' => $user->id],
                ['read_at' => now()]
            );
        }

        MessageRead::dispatch(
            $conversation->id,
            $latestMessage->id,
            $user->id,
            $user->name,
            now()->toIso8601String()
        );
    }

    /**
     * Archive or unarchive a conversation.
     */
    public function archiveConversation(Conversation $conversation, bool $archive = true): void
    {
        $conversation->update(['is_archived' => $archive]);
    }

    /**
     * Check if a user can access a conversation.
     */
    public function canAccessConversation(User $user, Conversation $conversation): bool
    {
        if ($user->isCitizen()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $conversation->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if a user can download a media attachment securely via centralized MediaManagementService.
     */
    public function canDownloadAttachment(User $user, MediaAttachment $media): bool
    {
        if ($user->isCitizen()) {
            return false;
        }

        return app(MediaManagementService::class)->canAccessMedia($user, $media);
    }
}
