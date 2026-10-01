<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\Response;
use JayI\Roster\Actions\DeleteOrganizationAction;
use JayI\Roster\Models\Organization;

final class DestroyOrganizationRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.delete';
    }

    protected function scope(): Organization
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
