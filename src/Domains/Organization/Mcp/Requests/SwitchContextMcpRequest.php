<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Domains\Organization\Actions\SwitchContextAction;
use JayI\Roster\Domains\User\Mcp\Requests\UserMcpRequest;
use Laravel\Mcp\ResponseFactory;

final class SwitchContextMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.users.update';
    }

    protected function self(): Model
    {
        return $this->targetUser();
    }

    protected function rules(): array
    {
        return SwitchContextAction::rules() + $this->userRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        unset($validated['user']);

        return $this->respond(app(SwitchContextAction::class)->execute($this->targetUser(), $validated));
    }
}
