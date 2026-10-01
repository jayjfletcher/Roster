<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\ShowTeamAction;
use JayI\Roster\Http\Resources\TeamResource;
use JayI\Roster\Models\Team;

final class ShowTeamRequest extends TeamRequest
{
    protected function ability(): string
    {
        return 'roster.teams.view';
    }

    protected function scope(): Team
    {
        return $this->team();
    }

    public function rules(): array
    {
        return ShowTeamAction::rules();
    }

    public function persist(): JsonResponse
    {
        return (new TeamResource(app(ShowTeamAction::class)->execute($this->team())))->response();
    }
}
