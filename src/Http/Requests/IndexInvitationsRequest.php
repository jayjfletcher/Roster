<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\ListInvitationsAction;
use JayI\Roster\Http\Resources\InvitationResource;
use JayI\Roster\Models\Organization;

final class IndexInvitationsRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.invitations.view';
    }

    protected function scope(): Organization
    {
        return $this->organization();
    }

    public function rules(): array
    {
        return ListInvitationsAction::rules();
    }

    public function persist(): JsonResponse
    {
        return InvitationResource::collection(app(ListInvitationsAction::class)->execute($this->organization(), $this->validated()))->response();
    }
}
