<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JayI\Roster\Support\Users;

/**
 * A user's identity alone, for nesting inside other payloads.
 *
 * @property Model $resource
 */
final class UserSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $users = app(Users::class);

        return [
            'id' => $this->resource->getRouteKey(),
            'name' => $users->name($this->resource),
            'email' => $users->email($this->resource),
        ];
    }
}
