<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JayI\Roster\Models\Impersonation;

/**
 * The link token is never serialized.
 *
 * @mixin Impersonation
 */
final class ImpersonationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user;
        $impersonator = $this->impersonator;

        return [
            'id' => $this->id,
            'user' => $user instanceof Model ? (new UserSummaryResource($user))->resolve($request) : null,
            'impersonator' => $impersonator instanceof Model ? (new UserSummaryResource($impersonator))->resolve($request) : null,
            'organization' => $this->organization?->slug,
            'reason' => $this->reason,
            'active' => $this->isActive(),
            'link_expires_at' => $this->link_expires_at->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'end_reason' => $this->end_reason,
        ];
    }
}
