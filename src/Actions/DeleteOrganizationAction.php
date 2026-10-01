<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Events\Action\OrganizationDeletedActionEvent;
use JayI\Roster\Events\Action\OrganizationDeletingActionEvent;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Profile;

final class DeleteOrganizationAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    /**
     * Delete an organization with its teams, memberships and invitations.
     * A personal organization goes only with its owner.
     */
    public function execute(Organization $organization, bool $force = false): void
    {
        if ($organization->personal && ! $force) {
            throw ValidationException::withMessages([
                'organization' => __('roster::roster.cannot_delete_personal'),
            ]);
        }

        OrganizationDeletingActionEvent::dispatch($organization);

        DB::transaction(function () use ($organization): void {
            // Not every host database enforces the nullOnDelete foreign keys
            // (SQLite without foreign_keys), so clear context explicitly.
            Profile::query()
                ->where('current_organization_id', $organization->getKey())
                ->update(['current_organization_id' => null, 'current_team_id' => null]);

            $organization->delete();
        });

        OrganizationDeletedActionEvent::dispatch($organization);
    }
}
