<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\ListTeamsAction;
use JayI\Roster\Http\Resources\TeamResource;
use JayI\Roster\Models\Organization;

final class IndexTeamsRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.teams.view';
    }

    protected function scope(): Organization
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
