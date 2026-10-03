<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Scim\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Organization\Http\Requests\OrganizationRequest;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Scim\Actions\ListScimTokensAction;
use JayI\Roster\Domains\Scim\Resources\ScimTokenResource;

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
