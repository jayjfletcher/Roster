<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\ListScimTokensAction;
use JayI\Roster\Http\Resources\ScimTokenResource;
use JayI\Roster\Models\Organization;

final class IndexScimTokensRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.scim.manage';
    }

    protected function scope(): Organization
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
