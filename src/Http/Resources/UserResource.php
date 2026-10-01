<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use JayI\Roster\Enums\UserStatus;
use JayI\Roster\Models\Profile;
use JayI\Roster\Roster;
use JayI\Roster\Support\Users;

/**
 * A user as every surface returns it: identity from the host model, profile
 * and status from Roster. A user with no profile row yet reads as an empty,
 * active profile.
 *
 * @property Model $resource
 */
final class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $users = app(Users::class);
        $user = $this->resource;

        $profile = $user->relationLoaded('rosterProfile') ? $user->getRelation('rosterProfile') : null;
        $profile = $profile instanceof Profile ? $profile : new Profile;

        $createdAt = $user->getAttribute($user->getCreatedAtColumn() ?? 'created_at');

        return [
            'id' => $user->getRouteKey(),
            'name' => $users->name($user),
            'email' => $users->email($user),
            'status' => ($profile->status ?? UserStatus::Active)->value,
            'status_reason' => $profile->status_reason,
            'status_changed_at' => $profile->status_changed_at?->toIso8601String(),
            'profile' => (new ProfileResource($profile))->resolve($request),
            'current_organization' => app(Roster::class)->organization($user)?->slug,
            'current_team' => app(Roster::class)->team($user)?->slug,
            'created_at' => $createdAt instanceof Carbon ? $createdAt->toIso8601String() : null,
        ];
    }
}
