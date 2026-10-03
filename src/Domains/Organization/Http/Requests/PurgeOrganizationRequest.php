<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\Response;
use JayI\Roster\Domains\Organization\Actions\PurgeOrganizationAction;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Http\Request;

/**
 * Works on deleted organizations too.
 */
final class PurgeOrganizationRequest extends Request
{
    private ?OrganizationModel $resolved = null;

    protected function ability(): string
    {
        return 'roster.organizations.purge';
    }

    protected function scope(): OrganizationModel
    {
        return $this->trashedOrganization();
    }

    public function rules(): array
    {
        return PurgeOrganizationAction::rules();
    }

    public function persist(): Response
    {
        app(PurgeOrganizationAction::class)->execute($this->trashedOrganization());

        return response()->noContent();
    }

    private function trashedOrganization(): OrganizationModel
    {
        return $this->resolved ??= OrganizationModel::withTrashed()->where('slug', $this->route('organization'))->firstOrFail();
    }
}
