<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\AddTeamMemberAction;
use JayI\Roster\Http\Resources\TeamResource;
use JayI\Roster\Models\Team;

final class StoreTeamMemberRequest extends TeamRequest
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
        return AddTeamMemberAction::rules();
    }

    public function persist(): JsonResponse
    {
        $team = app(AddTeamMemberAction::class)->execute($this->team(), $this->validated());

        return (new TeamResource($team))->response();
    }
}
