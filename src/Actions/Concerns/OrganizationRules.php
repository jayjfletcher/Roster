<?php

declare(strict_types=1);

namespace JayI\Roster\Actions\Concerns;

use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use JayI\Roster\Enums\UserStatus;
use JayI\Roster\Models\Organization;

/**
 * Validation shared by the Actions that write an organization's settings.
 */
trait OrganizationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected static function settingsRules(?Organization $organization = null): array
    {
        $domain = Rule::unique('roster_organization_domains', 'domain');

        if ($organization !== null) {
            $domain->where(fn (Builder $query): Builder => $query->where('organization_id', '!=', $organization->getKey()));
        }

        return [
            'auto_join' => ['sometimes', 'boolean'],
            // Accounts created through its SSO or SCIM: active, or waiting for approval.
            'provisioned_status' => ['sometimes', Rule::in([UserStatus::Active->value, UserStatus::Pending->value])],
            'domains' => ['sometimes', 'array', 'max:50'],
            'domains.*' => ['string', 'distinct', 'max:253', 'regex:/^(?!-)[a-z0-9-]+(\.[a-z0-9-]+)+$/i', $domain],
        ];
    }

    /**
     * Replace the organization's domains with the given list, lower-cased.
     *
     * @param  array<int, mixed>  $domains
     */
    private function syncDomains(Organization $organization, array $domains): void
    {
        $organization->domains()->delete();

        foreach (array_unique(array_map(fn (mixed $domain): string => strtolower((string) $domain), $domains)) as $domain) {
            $organization->domains()->create(['domain' => $domain]);
        }
    }
}
