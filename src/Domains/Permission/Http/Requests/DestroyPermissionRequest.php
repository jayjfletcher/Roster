<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Permission\Http\Requests;

use Illuminate\Http\Response;
use JayI\Roster\Domains\Permission\Actions\DeletePermissionAction;

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
