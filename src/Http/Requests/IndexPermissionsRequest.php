<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\ListPermissionsAction;
use JayI\Roster\Http\Request;
use JayI\Roster\Http\Resources\PermissionResource;

final class IndexPermissionsRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.roles.view';
    }

    public function rules(): array
    {
        return ListPermissionsAction::rules();
    }

    public function persist(): JsonResponse
    {
        return PermissionResource::collection(app(ListPermissionsAction::class)->execute($this->validated()))->response();
    }
}
