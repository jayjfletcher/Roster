<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso\Mcp\Requests;

use JayI\Roster\Domains\Sso\Actions\DeleteSsoConnectionAction;
use Laravel\Mcp\Response;

final class DeleteSsoConnectionMcpRequest extends SsoConnectionMcpRequest
{
    protected function ability(): string
    {
        return 'roster.sso.manage';
    }

    protected function rules(): array
    {
        return DeleteSsoConnectionAction::rules() + ['connection' => ['required', 'string']];
    }

    protected function handle(array $validated): Response
    {
        app(DeleteSsoConnectionAction::class)->execute($this->connection());

        return Response::text('SSO connection deleted.');
    }
}
