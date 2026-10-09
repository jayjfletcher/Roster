<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Impersonation\Actions\StartImpersonationAction;
use RefactorCircus\Roster\Domains\Impersonation\Resources\ImpersonationResource;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\User\Mcp\Requests\UserMcpRequest;
use RefactorCircus\Roster\Support\Scopes;

final class StartImpersonationMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.users.impersonate';
    }

    protected function scope(): OrganizationModel|TeamModel|null
    {
        return Scopes::fromInput($this->get('organization'));
    }

    protected function authorize(): bool
    {
        return $this->actor() !== null && parent::authorize();
    }

    protected function rules(): array
    {
        return StartImpersonationAction::rules() + $this->userRules();
    }

    protected function handle(array $validated): Response|ResponseFactory
    {
        $actor = $this->actor();

        if ($actor === null) {
            return Response::error('Unauthorized.');
        }

        unset($validated['user']);
        $started = app(StartImpersonationAction::class)->execute($this->targetUser(), $validated, $actor);

        return Response::structured([
            'data' => (new ImpersonationResource($started->impersonation))->resolve(),
            'url' => $started->url,
        ]);
    }
}
