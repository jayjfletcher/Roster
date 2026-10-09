<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationDomainModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationLinkModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * @mixin OrganizationModel
 */
final class OrganizationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $owner = $this->owner;

        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'owner' => $owner instanceof Model ? $owner->getRouteKey() : null,
            'personal' => $this->personal,
            'auto_join' => $this->auto_join,
            'provisioned_status' => $this->provisioned_status,
            'domains' => $this->whenLoaded('domains', fn (): array => $this->domains
                ->map(fn (OrganizationDomainModel $domain): string => $domain->domain)
                ->sort()
                ->values()
                ->all()),
            // Its records in external systems (ERP, CRM, ...), one per source.
            'links' => $this->links
                ->sortBy('source')
                ->map(fn (OrganizationLinkModel $link): array => [
                    'source' => $link->source,
                    'external_id' => $link->external_id,
                    'account_number' => $link->account_number,
                    'synced_at' => $link->synced_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'members_count' => $this->whenCounted('memberships'),
            'teams_count' => $this->whenCounted('teams'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
