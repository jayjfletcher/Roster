<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Team\Http\Requests;

use Illuminate\Http\Response;
use JayI\Roster\Domains\Team\Actions\RemoveTeamMemberAction;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Support\Users;

final class DestroyTeamMemberRequest extends TeamRequest
{
    protected function ability(): string
    {
        return 'roster.teams.manage';
    }

    protected function scope(): TeamModel
    {
        return $this->team();
    }

    public function rules(): array
    {
        return RemoveTeamMemberAction::rules();
    }

    public function persist(): Response
    {
        app(RemoveTeamMemberAction::class)->execute($this->team(), app(Users::class)->findOrFail($this->route('user')));

        return response()->noContent();
    }
}
