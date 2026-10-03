<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Sso\Actions\ShowSsoConnectionAction;
use JayI\Roster\Domains\Sso\Resources\SsoConnectionResource;

final class ShowSsoConnectionRequest extends SsoConnectionRequest
{
    protected function ability(): string
    {
        return 'roster.sso.view';
    }

    public function rules(): array
    {
        return ShowSsoConnectionAction::rules();
    }

    public function persist(): JsonResponse
    {
        return (new SsoConnectionResource(app(ShowSsoConnectionAction::class)->execute($this->connection())))->response();
    }
}
