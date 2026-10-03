<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Actions;

use JayI\Roster\Domains\Organization\Events\OrganizationShowingActionEvent;
use JayI\Roster\Domains\Organization\Events\OrganizationShownActionEvent;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;

final class ShowOrganizationAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(OrganizationModel $organization): OrganizationModel
    {
        OrganizationShowingActionEvent::dispatch($organization);

        $organization->load(['domains', 'links', 'owner'])->loadCount(['memberships', 'teams']);

        OrganizationShownActionEvent::dispatch($organization);

        return $organization;
    }
}
