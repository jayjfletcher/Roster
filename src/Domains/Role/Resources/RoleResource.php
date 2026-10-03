<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JayI\Roster\Domains\Permission\Models\PermissionModel;
use JayI\Roster\Domains\Role\Models\RoleModel;

/**
 * @mixin RoleModel
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
                ->map(fn (PermissionModel $permission): string => $permission->name)
                ->sort()
                ->values()
                ->all()),
        ];
    }
}
