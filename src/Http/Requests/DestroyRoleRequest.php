<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\Response;
use JayI\Roster\Actions\DeleteRoleAction;

final class DestroyRoleRequest extends RoleRequest
{
    protected function ability(): string
    {
        return 'roster.roles.manage';
    }

    public function rules(): array
    {
        return DeleteRoleAction::rules();
    }

    public function persist(): Response
    {
        app(DeleteRoleAction::class)->execute($this->role());

        return response()->noContent();
    }
}
