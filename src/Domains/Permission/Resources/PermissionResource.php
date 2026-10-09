<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;

/**
 * @mixin PermissionModel
 */
final class PermissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'system' => $this->system,
        ];
    }
}
