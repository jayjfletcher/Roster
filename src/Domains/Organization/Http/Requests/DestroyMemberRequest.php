<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\Response;
use JayI\Roster\Domains\Organization\Actions\RemoveMemberAction;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Support\Users;

final class DestroyMemberRequest extends OrganizationRequest
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
        return RemoveMemberAction::rules();
    }

    public function persist(): Response
    {
        app(RemoveMemberAction::class)->execute($this->organization(), app(Users::class)->findOrFail($this->route('user')));

        return response()->noContent();
    }
}
