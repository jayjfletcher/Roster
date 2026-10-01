<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use JayI\Roster\Actions\Concerns\OrganizationRules;
use JayI\Roster\Events\Action\OrganizationUpdatedActionEvent;
use JayI\Roster\Events\Action\OrganizationUpdatingActionEvent;
use JayI\Roster\Models\Organization;

final class UpdateOrganizationAction
{
    use OrganizationRules;

    /**
     * Pass the organization being updated so its own slug and domains pass
     * the unique checks.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(?Organization $organization = null): array
    {
        $slug = Rule::unique('roster_organizations', 'slug');

        if ($organization !== null) {
            $slug->ignore($organization->getKey());
        }

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'alpha_dash', 'max:255', $slug],
        ] + self::settingsRules($organization);
    }

    /**
     * Update an organization. A given `domains` list replaces the old one.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Organization $organization, array $data): Organization
    {
        OrganizationUpdatingActionEvent::dispatch($organization, $data);

        DB::transaction(function () use ($organization, $data): void {
            $organization->update(array_intersect_key($data, array_flip(['name', 'slug', 'auto_join'])));

            if (array_key_exists('domains', $data)) {
                $this->syncDomains($organization, (array) $data['domains']);
            }
        });

        $organization = $organization->refresh()->load('domains')->loadCount(['memberships', 'teams']);

        OrganizationUpdatedActionEvent::dispatch($organization);

        return $organization;
    }
}
