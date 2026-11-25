<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;

class IncidentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOffice();
    }

    public function view(User $user, Incident $incident): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isOffice()) {
            return $user->canReviewIncident($incident) || $user->isOfficeAdmin();
        }

        if ($user->isPersonnel()) {
            return $incident->assignments()
                ->where('personnel_id', $user->personnelProfile?->id)
                ->exists();
        }

        if ($user->isCitizen()) {
            return $incident->reporter_id === $user->id;
        }

        return false;
    }

    public function review(User $user, Incident $incident): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isOffice()) {
            return $user->canReviewIncident($incident);
        }

        return false;
    }

    public function override(User $user, Incident $incident): bool
    {
        return $user->isAdmin();
    }

    public function superOverride(User $user, Incident $incident): bool
    {
        return $user->isSuperAdmin();
    }

    public function assess(User $user, Incident $incident): bool
    {
        return $user->isAdmin();
    }

    public function callCitizen(User $user, Incident $incident): bool
    {
        return $user->isAdmin();
    }

    public function manageAdmins(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function assign(User $user, Incident $incident): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isOffice()) {
            return $user->officeUsers()->where('can_dispatch_units', true)->exists();
        }

        return false;
    }

    public function dispatch(User $user, Incident $incident): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isOffice()) {
            return $user->officeUsers()->where('can_dispatch_units', true)->exists();
        }

        return false;
    }

    public function updatePriority(User $user, Incident $incident): bool
    {
        return $user->isAdmin();
    }

    public function verifyContact(User $user, Incident $incident): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isOffice()) {
            return $user->canReviewIncident($incident);
        }

        return false;
    }
}
