<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Mcp\Requests;

use JayI\Roster\Domains\User\Actions\SuspendUserAction;
use Laravel\Mcp\ResponseFactory;

final class SuspendUserMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.users.manage-status';
    }

    protected function rules(): array
    {
        return SuspendUserAction::rules() + $this->userRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        unset($validated['user']);

        return $this->respond(app(SuspendUserAction::class)->execute($this->targetUser(), $validated, $this->actor()));
    }
}
