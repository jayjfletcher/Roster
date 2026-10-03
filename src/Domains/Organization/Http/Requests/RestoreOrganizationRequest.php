<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Organization\Actions\RestoreOrganizationAction;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Organization\Resources\OrganizationResource;
use JayI\Roster\Http\Request;

/**
 * Works on deleted organizations too.
 */
final class RestoreOrganizationRequest extends Request
{
    private ?OrganizationModel $resolved = null;

    protected function ability(): string
    {
        return 'roster.organizations.delete';
    }

    protected function scope(): OrganizationModel
    {
        return $this->trashedOrganization();
    }

    public function rules(): array
    {
        return RestoreOrganizationAction::rules();
    }

    public function persist(): JsonResponse
    {
        return (new OrganizationResource(app(RestoreOrganizationAction::class)->execute($this->trashedOrganization())))->response();
    }

    private function trashedOrganization(): OrganizationModel
    {
        return $this->resolved ??= OrganizationModel::withTrashed()->where('slug', $this->route('organization'))->firstOrFail();
    }
}
