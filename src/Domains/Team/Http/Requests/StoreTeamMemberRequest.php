<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Team\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Team\Actions\AddTeamMemberAction;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Domains\Team\Resources\TeamResource;

final class StoreTeamMemberRequest extends TeamRequest
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
        return AddTeamMemberAction::rules();
    }

    public function persist(): JsonResponse
    {
        $team = app(AddTeamMemberAction::class)->execute($this->team(), $this->validated());

        return (new TeamResource($team))->response();
    }
}
