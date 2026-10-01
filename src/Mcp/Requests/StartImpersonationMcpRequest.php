<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\StartImpersonationAction;
use JayI\Roster\Http\Resources\ImpersonationResource;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;
use JayI\Roster\Support\Scopes;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class StartImpersonationMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.users.impersonate';
    }

    protected function scope(): Organization|Team|null
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
