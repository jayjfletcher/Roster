<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Impersonation\Http\Requests;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Impersonation\Actions\StartImpersonationAction;
use JayI\Roster\Domains\Impersonation\Resources\ImpersonationResource;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Domains\User\Http\Requests\UserRequest;
use JayI\Roster\Support\Scopes;

final class StartImpersonationRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.users.impersonate';
    }

    protected function scope(): OrganizationModel|TeamModel|null
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
