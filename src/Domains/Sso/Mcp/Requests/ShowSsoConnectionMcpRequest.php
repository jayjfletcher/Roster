<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso\Mcp\Requests;

use JayI\Roster\Domains\Sso\Actions\ShowSsoConnectionAction;
use Laravel\Mcp\ResponseFactory;

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
