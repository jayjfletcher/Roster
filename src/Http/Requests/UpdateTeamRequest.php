<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\UpdateTeamAction;
use JayI\Roster\Http\Resources\TeamResource;
use JayI\Roster\Models\Team;

final class UpdateTeamRequest extends TeamRequest
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
        return UpdateTeamAction::rules($this->team());
    }

    public function persist(): JsonResponse
    {
        $team = app(UpdateTeamAction::class)->execute($this->team(), $this->validated());

        return (new TeamResource($team))->response();
    }
}
