<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Domains\Organization\Events\OrganizationPurgedActionEvent;
use JayI\Roster\Domains\Organization\Events\OrganizationPurgingActionEvent;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\User\Models\ProfileModel;

final class PurgeOrganizationAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    /**
     * Delete an organization for good, with its teams, memberships,
     * invitations, roles, SSO connections, SCIM tokens and links. It must be
     * deleted (trashed) first; `$personal` is for purging a user's personal
     * organization along with them.
     */
    public function execute(OrganizationModel $organization, bool $personal = false): void
    {
        if ($organization->personal && ! $personal) {
            throw ValidationException::withMessages(['organization' => __('roster::roster.cannot_delete_personal')]);
        }

        if (! $organization->trashed() && ! $personal) {
            throw ValidationException::withMessages(['organization' => __('roster::roster.delete_before_purge')]);
        }

        OrganizationPurgingActionEvent::dispatch($organization);

        DB::transaction(function () use ($organization): void {
            // Not every host database enforces the nullOnDelete foreign keys
            // (SQLite without foreign_keys), so clear context explicitly.
            ProfileModel::query()
                ->where('current_organization_id', $organization->getKey())
                ->update(['current_organization_id' => null, 'current_team_id' => null]);

            $organization->forceDelete();
        });

        OrganizationPurgedActionEvent::dispatch($organization);
    }
}
