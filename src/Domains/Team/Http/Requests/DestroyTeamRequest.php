<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Team\Http\Requests;

use Illuminate\Http\Response;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Team\Actions\DeleteTeamAction;

final class DestroyTeamRequest extends TeamRequest
{
    protected function ability(): string
    {
        return 'roster.teams.manage';
    }

    protected function scope(): OrganizationModel
    {
        return $this->organization();
    }

    public function rules(): array
    {
        return DeleteTeamAction::rules();
    }

    public function persist(): Response
    {
        app(DeleteTeamAction::class)->execute($this->team());

        return response()->noContent();
    }
}
