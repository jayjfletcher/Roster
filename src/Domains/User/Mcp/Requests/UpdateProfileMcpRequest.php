<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Domains\User\Actions\UpdateProfileAction;
use Laravel\Mcp\ResponseFactory;

final class UpdateProfileMcpRequest extends UserMcpRequest
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
        return UpdateProfileAction::rules() + $this->userRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        unset($validated['user']);

        return $this->respond(app(UpdateProfileAction::class)->execute($this->targetUser(), $validated));
    }
}
