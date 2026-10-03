<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\Response;
use JayI\Roster\Domains\Organization\Actions\DeleteOrganizationAction;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;

final class DestroyOrganizationRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.delete';
    }

    protected function scope(): OrganizationModel
    {
        return $this->organization();
    }

    public function rules(): array
    {
        return DeleteOrganizationAction::rules();
    }

    public function persist(): Response
    {
        app(DeleteOrganizationAction::class)->execute($this->organization());

        return response()->noContent();
    }
}
