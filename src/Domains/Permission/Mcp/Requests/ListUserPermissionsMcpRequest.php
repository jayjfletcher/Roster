<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Permission\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Domains\Permission\Actions\ListUserPermissionsAction;
use JayI\Roster\Domains\User\Mcp\Requests\UserMcpRequest;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class ListUserPermissionsMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.roles.view';
    }

    protected function self(): Model
    {
        return $this->targetUser();
    }

    protected function rules(): array
    {
        return ListUserPermissionsAction::rules() + $this->userRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        unset($validated['user']);

        return Response::structured(['data' => app(ListUserPermissionsAction::class)->execute($this->targetUser(), $validated)]);
    }
}
