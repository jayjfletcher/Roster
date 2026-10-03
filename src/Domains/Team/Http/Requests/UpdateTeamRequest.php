<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Team\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Team\Actions\UpdateTeamAction;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Domains\Team\Resources\TeamResource;

final class UpdateTeamRequest extends TeamRequest
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
        return UpdateTeamAction::rules($this->team());
    }

    public function persist(): JsonResponse
    {
        $team = app(UpdateTeamAction::class)->execute($this->team(), $this->validated());

        return (new TeamResource($team))->response();
    }
}
