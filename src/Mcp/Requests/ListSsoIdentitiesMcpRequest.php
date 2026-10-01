<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Actions\ListSsoIdentitiesAction;
use JayI\Roster\Http\Resources\SsoIdentityResource;
use Laravel\Mcp\ResponseFactory;

final class ListSsoIdentitiesMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.sso.view';
    }

    protected function self(): Model
    {
        return $this->targetUser();
    }

    protected function rules(): array
    {
        return ListSsoIdentitiesAction::rules() + $this->userRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $identities = app(ListSsoIdentitiesAction::class)->execute(['user' => $this->targetUser()->getRouteKey()] + $validated);

        return $this->structuredCollection(SsoIdentityResource::collection($identities->items())->resolve(), [
            'meta' => [
                'current_page' => $identities->currentPage(),
                'last_page' => $identities->lastPage(),
                'per_page' => $identities->perPage(),
                'total' => $identities->total(),
            ],
        ]);
    }
}
