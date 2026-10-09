<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Sso\Actions\UpdateSsoConnectionAction;

final class UpdateSsoConnectionMcpRequest extends SsoConnectionMcpRequest
{
    protected function ability(): string
    {
        return 'roster.sso.manage';
    }

    protected function rules(): array
    {
        return UpdateSsoConnectionAction::rules($this->connection()) + ['connection' => ['required', 'string']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        unset($validated['connection']);

        return $this->respondWithConnection(app(UpdateSsoConnectionAction::class)->execute($this->connection(), $validated));
    }
}
