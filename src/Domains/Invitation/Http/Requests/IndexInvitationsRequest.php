<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Invitation\Actions\ListInvitationsAction;
use RefactorCircus\Roster\Domains\Invitation\Resources\InvitationResource;
use RefactorCircus\Roster\Domains\Organization\Http\Requests\OrganizationRequest;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

final class IndexInvitationsRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.invitations.view';
    }

    protected function scope(): OrganizationModel
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
