<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Invitation\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Invitation\Actions\ListInvitationsAction;
use JayI\Roster\Domains\Invitation\Resources\InvitationResource;
use JayI\Roster\Domains\Organization\Http\Requests\OrganizationRequest;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;

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
