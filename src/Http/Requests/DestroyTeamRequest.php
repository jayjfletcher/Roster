<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\Response;
use JayI\Roster\Actions\DeleteTeamAction;
use JayI\Roster\Models\Organization;

final class DestroyTeamRequest extends TeamRequest
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
        return DeleteTeamAction::rules();
    }

    public function persist(): Response
    {
        app(DeleteTeamAction::class)->execute($this->team());

        return response()->noContent();
    }
}
