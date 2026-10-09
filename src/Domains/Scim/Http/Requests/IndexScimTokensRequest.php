<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Http\Requests\OrganizationRequest;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Scim\Actions\ListScimTokensAction;
use RefactorCircus\Roster\Domains\Scim\Resources\ScimTokenResource;

final class IndexScimTokensRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.scim.manage';
    }

    protected function scope(): OrganizationModel
    {
        return $this->organization();
    }

    public function rules(): array
    {
        return ListScimTokensAction::rules();
    }

    public function persist(): JsonResponse
    {
        return ScimTokenResource::collection(app(ListScimTokensAction::class)->execute($this->organization(), $this->validated()))->response();
    }
}
