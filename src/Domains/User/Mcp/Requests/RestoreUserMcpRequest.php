<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\User\Actions\RestoreUserAction;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;
use RefactorCircus\Roster\Mcp\Request;
use RefactorCircus\Roster\Support\Users;

/**
 * Works on deleted records too.
 */
final class RestoreUserMcpRequest extends Request
{
    private ?Model $resolved = null;

    protected function ability(): string
    {
        return 'roster.users.delete';
    }

    protected function rules(): array
    {
        return RestoreUserAction::rules() + ['user' => ['required']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        return Response::structured(['data' => (new UserResource(app(RestoreUserAction::class)->execute($this->trashed())))->resolve()]);
    }

    private function trashed(): Model
    {
        return $this->resolved ??= app(Users::class)->findWithTrashedOrFail($this->get('user'));
    }
}
