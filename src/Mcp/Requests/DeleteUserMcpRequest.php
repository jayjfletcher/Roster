<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\DeleteUserAction;
use Laravel\Mcp\Response;

final class DeleteUserMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.users.delete';
    }

    protected function rules(): array
    {
        return DeleteUserAction::rules() + $this->userRules();
    }

    protected function handle(array $validated): Response
    {
        app(DeleteUserAction::class)->execute($this->targetUser(), $this->actor());

        return Response::text('User deleted.');
    }
}
