<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso\Mcp\Requests;

use JayI\Roster\Domains\Sso\Actions\UpdateSsoConnectionAction;
use Laravel\Mcp\ResponseFactory;

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
