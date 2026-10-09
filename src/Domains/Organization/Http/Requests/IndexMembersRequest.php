<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Actions\ListMembersAction;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Organization\Resources\MemberResource;

final class IndexMembersRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.members.view';
    }

    protected function scope(): OrganizationModel
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
