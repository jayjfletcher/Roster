<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Events\Action\OrganizationRestoredActionEvent;
use JayI\Roster\Events\Action\OrganizationRestoringActionEvent;
use JayI\Roster\Models\Organization;

final class RestoreOrganizationAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    /**
     * Bring back a deleted organization with everything it kept. Its slug and
     * domains stayed reserved, so nothing can clash.
     */
    public function execute(Organization $organization): Organization
    {
        if (! $organization->trashed()) {
            throw ValidationException::withMessages(['organization' => __('roster::roster.not_deleted')]);
        }

        OrganizationRestoringActionEvent::dispatch($organization);

        DB::transaction(fn () => $organization->restore());

        $organization = $organization->load(['domains', 'links', 'owner'])->loadCount(['memberships', 'teams']);

        OrganizationRestoredActionEvent::dispatch($organization);

        return $organization;
    }
}
