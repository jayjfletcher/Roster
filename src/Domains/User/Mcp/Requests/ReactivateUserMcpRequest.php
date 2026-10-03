<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Mcp\Requests;

use JayI\Roster\Domains\User\Actions\ReactivateUserAction;
use Laravel\Mcp\ResponseFactory;

final class ReactivateUserMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.users.manage-status';
    }

    protected function rules(): array
    {
        return ReactivateUserAction::rules() + $this->userRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        unset($validated['user']);

        return $this->respond(app(ReactivateUserAction::class)->execute($this->targetUser(), $validated, $this->actor()));
    }
}
