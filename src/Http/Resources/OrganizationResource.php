<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\OrganizationDomain;

/**
 * @mixin Organization
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
            'domains' => $this->whenLoaded('domains', fn (): array => $this->domains
                ->map(fn (OrganizationDomain $domain): string => $domain->domain)
                ->sort()
                ->values()
                ->all()),
            'members_count' => $this->whenCounted('memberships'),
            'teams_count' => $this->whenCounted('teams'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
