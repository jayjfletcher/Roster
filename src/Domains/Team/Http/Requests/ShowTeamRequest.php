<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Team\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Team\Actions\ShowTeamAction;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Domains\Team\Resources\TeamResource;

final class ShowTeamRequest extends TeamRequest
{
    protected function ability(): string
    {
        return 'roster.teams.view';
    }

    protected function scope(): TeamModel
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
