<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Http\Requests;

use Illuminate\Http\Response;
use JayI\Roster\Domains\User\Actions\DeleteUserAction;

final class DestroyUserRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.users.delete';
    }

    public function rules(): array
    {
        return DeleteUserAction::rules();
    }

    public function persist(): Response
    {
        app(DeleteUserAction::class)->execute($this->targetUser(), $this->actor());

        return response()->noContent();
    }
}
