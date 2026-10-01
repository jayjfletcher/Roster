<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\AddMemberAction;
use JayI\Roster\Http\Resources\MemberResource;
use JayI\Roster\Models\Organization;

final class StoreMemberRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.members.manage';
    }

    protected function scope(): Organization
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
