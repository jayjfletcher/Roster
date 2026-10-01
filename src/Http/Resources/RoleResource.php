<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JayI\Roster\Models\Permission;
use JayI\Roster\Models\Role;

/**
 * @mixin Role
 */
final class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'scope' => $this->scope->value,
            'organization' => $this->organization?->slug,
            'description' => $this->description,
            'super' => $this->super,
            'system' => $this->system,
            'permissions' => $this->whenLoaded('permissions', fn (): array => $this->permissions
                ->map(fn (Permission $permission): string => $permission->name)
                ->sort()
                ->values()
                ->all()),
        ];
    }
}
