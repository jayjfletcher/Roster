<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;

/**
 * @mixin ProfileModel
 */
final class ProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'display_name' => $this->display_name,
            'avatar_url' => $this->avatar_url,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'bio' => $this->bio,
            'meta' => $this->meta ?? (object) [],
        ];
    }
}
