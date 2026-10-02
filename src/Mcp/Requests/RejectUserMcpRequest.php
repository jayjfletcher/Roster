<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\RejectUserAction;
use Laravel\Mcp\ResponseFactory;

final class RejectUserMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.users.approve';
    }

    protected function rules(): array
    {
        return RejectUserAction::rules() + $this->userRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        unset($validated['user']);

        return $this->respond(app(RejectUserAction::class)->execute($this->targetUser(), $validated, $this->actor()));
    }
}
