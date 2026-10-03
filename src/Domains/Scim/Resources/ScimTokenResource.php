<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Scim\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;
use JayI\Roster\Domains\Scim\Models\ScimTokenModel;

/**
 * The token itself is never serialized; it is returned once, at issue.
 *
 * @mixin ScimTokenModel
 */
final class ScimTokenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $organization = $this->organization;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'organization' => $organization?->slug,
            'sso_connection' => $this->ssoConnection?->slug,
            'base_url' => $organization !== null && Route::has('roster.scim.users.index') ? str_replace('/Users', '', route('roster.scim.users.index', $organization->slug)) : null,
            'usable' => $this->isUsable(),
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'revoked_at' => $this->revoked_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
