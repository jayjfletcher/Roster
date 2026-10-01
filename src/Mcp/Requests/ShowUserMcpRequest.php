<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Actions\ShowUserAction;
use Laravel\Mcp\ResponseFactory;

final class ShowUserMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.users.view';
    }

    protected function self(): Model
    {
        return $this->targetUser();
    }

    protected function rules(): array
    {
        return ShowUserAction::rules() + $this->userRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return $this->respond(app(ShowUserAction::class)->execute($this->targetUser()));
    }
}
