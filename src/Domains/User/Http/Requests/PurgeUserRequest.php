<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use RefactorCircus\Roster\Domains\User\Actions\PurgeUserAction;
use RefactorCircus\Roster\Http\Request;
use RefactorCircus\Roster\Support\Users;

/**
 * Works on deleted users too.
 */
final class PurgeUserRequest extends Request
{
    private ?Model $resolved = null;

    protected function ability(): string
    {
        return 'roster.users.purge';
    }

    public function rules(): array
    {
        return PurgeUserAction::rules();
    }

    public function persist(): Response
    {
        app(PurgeUserAction::class)->execute($this->trashedUser(), $this->actor());

        return response()->noContent();
    }

    private function trashedUser(): Model
    {
        return $this->resolved ??= app(Users::class)->findWithTrashedOrFail($this->route('user'));
    }
}
