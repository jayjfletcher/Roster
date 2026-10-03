<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Domains\Organization\Events\OrganizationRestoredActionEvent;
use JayI\Roster\Domains\Organization\Events\OrganizationRestoringActionEvent;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;

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
    public function execute(OrganizationModel $organization): OrganizationModel
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
