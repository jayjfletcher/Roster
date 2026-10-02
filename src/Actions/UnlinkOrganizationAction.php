<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Events\Action\OrganizationUnlinkedActionEvent;
use JayI\Roster\Events\Action\OrganizationUnlinkingActionEvent;
use JayI\Roster\Models\Organization;

final class UnlinkOrganizationAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    /**
     * Forget the organization's record in one external system. The next sync
     * of that record creates a new organization unless it names this one.
     */
    public function execute(Organization $organization, string $source): Organization
    {
        $link = $organization->links()->where('source', $source)->first()
            ?? throw ValidationException::withMessages(['source' => __('roster::roster.not_linked', ['source' => $source])]);

        OrganizationUnlinkingActionEvent::dispatch($organization, $source);

        DB::transaction(fn () => $link->delete());

        $organization = $organization->refresh()->load(['domains', 'links'])->loadCount(['memberships', 'teams']);

        OrganizationUnlinkedActionEvent::dispatch($organization, $source);

        return $organization;
    }
}
