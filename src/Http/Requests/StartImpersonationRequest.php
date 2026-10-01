<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\StartImpersonationAction;
use JayI\Roster\Http\Resources\ImpersonationResource;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;
use JayI\Roster\Support\Scopes;

final class StartImpersonationRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.users.impersonate';
    }

    protected function scope(): Organization|Team|null
    {
        return Scopes::fromInput($this->input('organization'));
    }

    public function rules(): array
    {
        return StartImpersonationAction::rules();
    }

    /**
     * The link is in the response once; only the browser signed in as the
     * caller can use it.
     */
    public function persist(): JsonResponse
    {
        $actor = $this->actor() ?? throw new AuthenticationException;
        $started = app(StartImpersonationAction::class)->execute($this->targetUser(), $this->validated(), $actor);

        return (new ImpersonationResource($started->impersonation))
            ->additional(['url' => $started->url])
            ->response()
            ->setStatusCode(201);
    }
}
