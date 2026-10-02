<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\RestoreOrganizationAction;
use JayI\Roster\Http\Request;
use JayI\Roster\Http\Resources\OrganizationResource;
use JayI\Roster\Models\Organization;

/**
 * Works on deleted organizations too.
 */
final class RestoreOrganizationRequest extends Request
{
    private ?Organization $resolved = null;

    protected function ability(): string
    {
        return 'roster.organizations.delete';
    }

    protected function scope(): Organization
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

    private function trashedOrganization(): Organization
    {
        return $this->resolved ??= Organization::withTrashed()->where('slug', $this->route('organization'))->firstOrFail();
    }
}
