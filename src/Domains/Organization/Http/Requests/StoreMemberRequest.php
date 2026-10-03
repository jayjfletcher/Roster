<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Organization\Actions\AddMemberAction;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Organization\Resources\MemberResource;

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
