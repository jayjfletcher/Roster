<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JayI\Roster\Models\SsoIdentity;

/**
 * @mixin SsoIdentity
 */
final class SsoIdentityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user;

        return [
            'id' => $this->id,
            'connection' => $this->connection?->slug,
            'organization' => $this->connection?->organization?->slug,
            'user' => $user instanceof Model ? (new UserSummaryResource($user))->resolve($request) : null,
            'subject' => $this->subject,
            'email' => $this->email,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'linked_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
