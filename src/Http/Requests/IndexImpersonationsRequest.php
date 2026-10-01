<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\ListImpersonationsAction;
use JayI\Roster\Http\Request;
use JayI\Roster\Http\Resources\ImpersonationResource;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;
use JayI\Roster\Support\Scopes;

final class IndexImpersonationsRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.users.impersonate';
    }

    protected function scope(): Organization|Team|null
    {
        return Scopes::fromInput($this->input('organization'));
    }

    public function rules(): array
    {
        return ListImpersonationsAction::rules();
    }

    public function persist(): JsonResponse
    {
        return ImpersonationResource::collection(app(ListImpersonationsAction::class)->execute($this->validated()))->response();
    }
}
