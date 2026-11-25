<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\IncidentAssignment;
use App\Models\MediaAttachment;
use App\Models\OfficeResponsibility;
use App\Models\ResponseTeam;
use App\Models\User;

class MediaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function manageSettings(User $user): bool
    {
        return $user->isAdmin();
    }

    public function archive(User $user, MediaAttachment $media): bool
    {
        return $user->isAdmin();
    }

    public function purge(User $user, MediaAttachment $media): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, MediaAttachment $media): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Uploader always owns view right for files they uploaded themselves
        if ($media->uploader_id !== null && (int) $media->uploader_id === (int) $user->id) {
            return true;
        }

        if ($user->isCitizen()) {
            // Citizen can only view media linked to an incident they reported
            if ($media->incident_id !== null && $media->incident && (int) $media->incident->reporter_id === (int) $user->id) {
                return true;
            }

            return $media->evidenceRecords()
                ->whereHas('incident', fn ($q) => $q->where('reporter_id', $user->id))
                ->exists();
        }

        // Check conversation membership if attached to an internal messenger thread
        if ($media->conversation_id !== null) {
            $inDirectConv = $media->conversation()
                ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
                ->exists();
            if ($inDirectConv) {
                return true;
            }
        }

        $inPivotConv = $media->messages()
            ->whereHas('conversation.participants', fn ($q) => $q->where('user_id', $user->id))
            ->exists();
        if ($inPivotConv) {
            return true;
        }

        // Check incident-level authorization for Personnel & Office accounts
        $incidentIds = $media->evidenceRecords()->pluck('incident_id');
        if ($media->incident_id !== null) {
            $incidentIds->push($media->incident_id);
        }
        $incidentIds = $incidentIds->filter()->unique()->values();

        if ($incidentIds->isEmpty()) {
            return false;
        }

        if ($user->isPersonnel()) {
            $personnel = $user->personnelProfile;
            if (! $personnel) {
                return false;
            }

            $teamIds = $personnel->teams()->pluck('response_teams.id');
            $jurisdictionBarangayIds = $personnel->jurisdictions()->pluck('barangay_id');

            return IncidentAssignment::whereIn('incident_id', $incidentIds)
                ->where(function ($q) use ($personnel, $teamIds) {
                    $q->where('personnel_id', $personnel->id)
                        ->orWhereIn('team_id', $teamIds);
                })
                ->exists()
                || $media->evidenceRecords()->whereHas('incident', function ($q) use ($jurisdictionBarangayIds) {
                    $q->whereIn('barangay_id', $jurisdictionBarangayIds);
                })->exists();
        }

        if ($user->isOffice()) {
            $office = $user->primaryOffice();
            if (! $office) {
                return false;
            }

            $teamIds = ResponseTeam::where('office_id', $office->id)->pluck('id');
            $catSlugs = OfficeResponsibility::where('office_id', $office->id)->pluck('category_slug');

            return IncidentAssignment::whereIn('incident_id', $incidentIds)
                ->where(function ($q) use ($office, $teamIds) {
                    $q->where('office_id', $office->id)
                        ->orWhereIn('team_id', $teamIds);
                })
                ->exists()
                || $media->evidenceRecords()->whereHas('incident.category', fn ($q) => $q->whereIn('slug', $catSlugs))->exists();
        }

        return false;
    }

    public function download(User $user, MediaAttachment $media): bool
    {
        return $this->view($user, $media);
    }
}
