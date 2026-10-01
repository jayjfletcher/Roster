<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\UpdateUserAction;
use Laravel\Mcp\ResponseFactory;

final class UpdateUserMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.users.update';
    }

    protected function rules(): array
    {
        return UpdateUserAction::rules($this->targetUser()) + $this->userRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        unset($validated['user']);

        return $this->respond(app(UpdateUserAction::class)->execute($this->targetUser(), $validated));
    }
}
