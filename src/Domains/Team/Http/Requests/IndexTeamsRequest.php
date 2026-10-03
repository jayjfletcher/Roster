<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Team\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Organization\Http\Requests\OrganizationRequest;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Team\Actions\ListTeamsAction;
use JayI\Roster\Domains\Team\Resources\TeamResource;

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
