<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\RestoreUserAction;
use JayI\Roster\Http\Request;
use JayI\Roster\Http\Resources\UserResource;
use JayI\Roster\Support\Users;

/**
 * Works on deleted users too.
 */
final class RestoreUserRequest extends Request
{
    private ?Model $resolved = null;

    protected function ability(): string
    {
        return 'roster.users.delete';
    }

    public function rules(): array
    {
        return RestoreUserAction::rules();
    }

    public function persist(): JsonResponse
    {
        return (new UserResource(app(RestoreUserAction::class)->execute($this->trashedUser())))->response();
    }

    private function trashedUser(): Model
    {
        return $this->resolved ??= app(Users::class)->findWithTrashedOrFail($this->route('user'));
    }
}
