<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\ListUserPermissionsAction;

final class ShowUserPermissionsRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.roles.view';
    }

    protected function self(): Model
    {
        return $this->targetUser();
    }

    public function rules(): array
    {
        return ListUserPermissionsAction::rules();
    }

    public function persist(): JsonResponse
    {
        return response()->json(['data' => app(ListUserPermissionsAction::class)->execute($this->targetUser(), $this->validated())]);
    }
}
