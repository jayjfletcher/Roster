<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\Response;
use JayI\Roster\Actions\DeletePermissionAction;

final class DestroyPermissionRequest extends PermissionRequest
{
    protected function ability(): string
    {
        return 'roster.roles.manage';
    }

    public function rules(): array
    {
        return DeletePermissionAction::rules();
    }

    public function persist(): Response
    {
        app(DeletePermissionAction::class)->execute($this->permission());

        return response()->noContent();
    }
}
