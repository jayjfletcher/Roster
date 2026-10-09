<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Http\Requests\OrganizationRequest;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Actions\ListTeamsAction;
use RefactorCircus\Roster\Domains\Team\Resources\TeamResource;

final class IndexTeamsRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.teams.view';
    }

    protected function scope(): OrganizationModel
    {
        return $this->organization();
    }

    public function rules(): array
    {
        return ListTeamsAction::rules();
    }

    public function persist(): JsonResponse
    {
        return TeamResource::collection(app(ListTeamsAction::class)->execute($this->organization(), $this->validated()))->response();
    }
}
