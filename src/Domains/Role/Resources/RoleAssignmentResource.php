<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\User\Resources\UserSummaryResource;

/**
 * @mixin RoleAssignmentModel
 */
final class RoleAssignmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user;

        return [
            'id' => $this->id,
            'user' => $user instanceof Model ? (new UserSummaryResource($user))->resolve($request) : null,
            'role' => $this->role === null ? null : [
                'id' => $this->role->id,
                'slug' => $this->role->slug,
                'name' => $this->role->name,
                'scope' => $this->role->scope->value,
            ],
            'organization' => $this->organization?->slug,
            'team' => $this->team?->slug,
            'assigned_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
