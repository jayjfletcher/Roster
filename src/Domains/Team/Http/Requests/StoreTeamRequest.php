<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Team\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Organization\Http\Requests\OrganizationRequest;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Team\Actions\CreateTeamAction;
use JayI\Roster\Domains\Team\Resources\TeamResource;

final class StoreTeamRequest extends OrganizationRequest
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
        return CreateTeamAction::rules($this->organization());
    }

    public function persist(): JsonResponse
    {
        $team = app(CreateTeamAction::class)->execute($this->organization(), $this->validated());

        return (new TeamResource($team))->response()->setStatusCode(201);
    }
}
