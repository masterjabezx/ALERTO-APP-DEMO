<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, $id): bool {
    return (int) $user->id === (int) $id;
});

// LDRRMO Super Admin Operations Live Map & Override Channel
Broadcast::channel('super-admin.operations', function (User $user): bool {
    return $user->isSuperAdmin();
});

// Admin Operations Center Live Map Channel
Broadcast::channel('admin.operations', function (User $user): bool {
    return $user->isAdmin();
});

// Agency Office Channel (Strict Multi-Tenant Isolation)
Broadcast::channel('office.{officeId}', function (User $user, $officeId): bool {
    if ($user->isAdmin()) {
        return true;
    }

    if (! $user->isOffice()) {
        return false;
    }

    return $user->officeUsers()->where('office_id', $officeId)->exists();
});

// Responding Personnel Channel (Authorized Field Responders)
Broadcast::channel('personnel.{userId}', function (User $user, $userId): bool {
    if ($user->isAdmin()) {
        return true;
    }

    if (! $user->isPersonnel()) {
        return false;
    }

    return (int) $user->id === (int) $userId;
});

// Internal Messenger Private Conversation Channel
Broadcast::channel('conversation.{conversationId}', function (User $user, $conversationId): bool {
    if ($user->isCitizen()) {
        return false;
    }

    if ($user->isAdmin()) {
        return true;
    }

    $conversation = Conversation::find($conversationId);
    if (! $conversation) {
        return false;
    }

    return $conversation->participants()->where('user_id', $user->id)->exists();
});

// User Direct Notification Channel
Broadcast::channel('user.{userId}', function (User $user, $userId): bool {
    if ($user->isCitizen()) {
        return false;
    }

    return (int) $user->id === (int) $userId;
});

// Admin Facebook Messenger Channel
Broadcast::channel('admin.facebook-messenger', function (User $user): bool {
    return $user->isAdmin();
});
