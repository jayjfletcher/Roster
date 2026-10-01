<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\UpdatePermissionAction;
use JayI\Roster\Http\Resources\PermissionResource;

final class UpdatePermissionRequest extends PermissionRequest
{
    protected function ability(): string
    {
        return 'roster.roles.manage';
    }

    public function rules(): array
    {
        return UpdatePermissionAction::rules();
    }

    public function persist(): JsonResponse
    {
        return (new PermissionResource(app(UpdatePermissionAction::class)->execute($this->permission(), $this->validated())))->response();
    }
}
