<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JayI\Roster\Domains\Organization\Models\MembershipModel;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Domains\User\Resources\UserSummaryResource;
use JayI\Roster\Support\Users;

/**
 * @mixin MembershipModel
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
            'status' => $user instanceof Model ? app(Users::class)->status($user)->value : null,
            'source' => $this->source->value,
            'teams' => $this->whenLoaded('teams', fn (): array => $this->teams
                ->map(fn (TeamModel $team): string => $team->slug)
                ->sort()
                ->values()
                ->all()),
            'joined_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
