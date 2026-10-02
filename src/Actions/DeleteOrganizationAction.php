<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Events\Action\OrganizationDeletedActionEvent;
use JayI\Roster\Events\Action\OrganizationDeletingActionEvent;
use JayI\Roster\Models\Organization;

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
     * Delete an organization. It's a soft delete: everything it has is kept,
     * its slug and domains stay reserved, and it can be restored until it's
     * purged (PurgeOrganizationAction / roster:purge-deleted). A personal
     * organization goes only with its owner.
     */
    public function execute(Organization $organization, bool $force = false): void
    {
        if ($organization->personal && ! $force) {
            throw ValidationException::withMessages([
                'organization' => __('roster::roster.cannot_delete_personal'),
            ]);
        }

        OrganizationDeletingActionEvent::dispatch($organization);

        DB::transaction(fn () => $organization->delete());

        OrganizationDeletedActionEvent::dispatch($organization);
    }
}
