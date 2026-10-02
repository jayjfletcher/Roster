<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use JayI\Roster\Events\Action\OrganizationShowingActionEvent;
use JayI\Roster\Events\Action\OrganizationShownActionEvent;
use JayI\Roster\Models\Organization;

final class ShowOrganizationAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(Organization $organization): Organization
    {
        OrganizationShowingActionEvent::dispatch($organization);

        $organization->load(['domains', 'links', 'owner'])->loadCount(['memberships', 'teams']);

        OrganizationShownActionEvent::dispatch($organization);

        return $organization;
    }
}
