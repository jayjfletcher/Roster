<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\ListMembersAction;
use JayI\Roster\Http\Resources\MemberResource;
use JayI\Roster\Models\Organization;

final class IndexMembersRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.members.view';
    }

    protected function scope(): Organization
    {
        return $this->organization();
    }

    public function rules(): array
    {
        return ListMembersAction::rules();
    }

    public function persist(): JsonResponse
    {
        return MemberResource::collection(app(ListMembersAction::class)->execute($this->organization(), $this->validated()))->response();
    }
}
