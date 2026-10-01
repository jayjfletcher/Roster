<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JayI\Roster\Models\Membership;
use JayI\Roster\Models\Team;

/**
 * @mixin Membership
 */
final class MemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user;
        $organization = $this->organization;

        return [
            'user' => $user instanceof Model ? (new UserSummaryResource($user))->resolve($request) : null,
            'owner' => $user instanceof Model && $organization !== null && $organization->isOwnedBy($user),
            'source' => $this->source->value,
            'teams' => $this->whenLoaded('teams', fn (): array => $this->teams
                ->map(fn (Team $team): string => $team->slug)
                ->sort()
                ->values()
                ->all()),
            'joined_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
