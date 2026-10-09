<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Sso\Actions\ShowSsoConnectionAction;

final class ShowSsoConnectionMcpRequest extends SsoConnectionMcpRequest
{
    protected function ability(): string
    {
        return 'roster.sso.view';
    }

    protected function rules(): array
    {
        return ShowSsoConnectionAction::rules() + ['connection' => ['required', 'string']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        return $this->respondWithConnection(app(ShowSsoConnectionAction::class)->execute($this->connection()));
    }
}
