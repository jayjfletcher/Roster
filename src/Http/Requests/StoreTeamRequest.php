<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\CreateTeamAction;
use JayI\Roster\Http\Resources\TeamResource;
use JayI\Roster\Models\Organization;

final class StoreTeamRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.teams.manage';
    }

    protected function scope(): Organization
    {
        return $this->organization();
    }

    public function rules(): array
    {
        return CreateTeamAction::rules($this->organization());
    }

    public function persist(): JsonResponse
    {
        $team = app(CreateTeamAction::class)->execute($this->organization(), $this->validated());

        return (new TeamResource($team))->response()->setStatusCode(201);
    }
}
