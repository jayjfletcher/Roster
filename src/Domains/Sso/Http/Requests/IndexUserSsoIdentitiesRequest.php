<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Sso\Actions\ListSsoIdentitiesAction;
use JayI\Roster\Domains\Sso\Resources\SsoIdentityResource;
use JayI\Roster\Domains\User\Http\Requests\UserRequest;

final class IndexUserSsoIdentitiesRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.sso.view';
    }

    protected function self(): Model
    {
        return $this->targetUser();
    }

    public function rules(): array
    {
        return ListSsoIdentitiesAction::rules();
    }

    public function persist(): JsonResponse
    {
        $filters = ['user' => $this->targetUser()->getRouteKey()] + $this->validated();

        return SsoIdentityResource::collection(app(ListSsoIdentitiesAction::class)->execute($filters))->response();
    }
}
