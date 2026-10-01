<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\Response;
use JayI\Roster\Actions\RemoveTeamMemberAction;
use JayI\Roster\Models\Team;
use JayI\Roster\Support\Users;

final class DestroyTeamMemberRequest extends TeamRequest
{
    protected function ability(): string
    {
        return 'roster.teams.manage';
    }

    protected function scope(): Team
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
