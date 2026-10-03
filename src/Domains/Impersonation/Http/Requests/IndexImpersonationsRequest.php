<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Impersonation\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Impersonation\Actions\ListImpersonationsAction;
use JayI\Roster\Domains\Impersonation\Resources\ImpersonationResource;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Http\Request;
use JayI\Roster\Support\Scopes;

final class IndexImpersonationsRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.users.impersonate';
    }

    protected function scope(): OrganizationModel|TeamModel|null
    {
        return Scopes::fromInput($this->input('organization'));
    }

    protected function acrossOrganizations(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ListImpersonationsAction::rules();
    }

    public function persist(): JsonResponse
    {
        return ImpersonationResource::collection(app(ListImpersonationsAction::class)->execute($this->validated(), $this->organizations()))->response();
    }
}
