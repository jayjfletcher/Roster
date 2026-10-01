<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\ListRolesAction;
use JayI\Roster\Http\Request;
use JayI\Roster\Http\Resources\RoleResource;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;
use JayI\Roster\Support\Scopes;

final class IndexRolesRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.roles.view';
    }

    protected function scope(): Organization|Team|null
    {
        return Scopes::fromInput($this->input('organization'), $this->input('team'));
    }

    public function rules(): array
    {
        return ListRolesAction::rules();
    }

    public function persist(): JsonResponse
    {
        return RoleResource::collection(app(ListRolesAction::class)->execute($this->validated()))->response();
    }
}
