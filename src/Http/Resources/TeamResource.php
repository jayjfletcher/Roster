<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JayI\Roster\Models\Membership;
use JayI\Roster\Models\Team;

/**
 * @mixin Team
 */
final class TeamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'organization' => $this->organization?->slug,
            'members_count' => $this->whenCounted('seats'),
            'members' => $this->whenLoaded('memberships', fn (): array => $this->memberships
                ->map(fn (Membership $membership): ?Model => $membership->user)
                ->filter()
                ->map(fn (Model $user): array => (new UserSummaryResource($user))->resolve($request))
                ->values()
                ->all()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
