<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Invitation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JayI\Roster\Domains\Invitation\Models\InvitationModel;
use JayI\Roster\Domains\Team\Models\TeamModel;

/**
 * The token is never serialized: it exists only in the invitation email.
 *
 * @mixin InvitationModel
 */
final class InvitationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'organization' => $this->organization?->slug,
            'teams' => $this->relationLoaded('teamModels')
                ? $this->getRelation('teamModels')->pluck('slug')->all()
                : TeamModel::query()->whereIn('id', (array) $this->teams)->orderBy('slug')->pluck('slug')->all(),
            'status' => $this->status()->value,
            'expires_at' => $this->expires_at->toIso8601String(),
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'declined_at' => $this->declined_at?->toIso8601String(),
            'revoked_at' => $this->revoked_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
