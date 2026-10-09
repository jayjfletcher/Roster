<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Organization\Resources\MemberResource;

final class StoreMemberRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.members.manage';
    }

    protected function scope(): OrganizationModel
    {
        return $this->organization();
    }

    public function rules(): array
    {
        return AddMemberAction::rules();
    }

    public function persist(): JsonResponse
    {
        $membership = app(AddMemberAction::class)->execute($this->organization(), $this->validated());

        return (new MemberResource($membership))->response()->setStatusCode(201);
    }
}
