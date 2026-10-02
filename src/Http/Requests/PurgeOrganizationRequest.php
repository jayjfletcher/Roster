<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\Response;
use JayI\Roster\Actions\PurgeOrganizationAction;
use JayI\Roster\Http\Request;
use JayI\Roster\Models\Organization;

/**
 * Works on deleted organizations too.
 */
final class PurgeOrganizationRequest extends Request
{
    private ?Organization $resolved = null;

    protected function ability(): string
    {
        return 'roster.organizations.purge';
    }

    protected function scope(): Organization
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

    private function trashedOrganization(): Organization
    {
        return $this->resolved ??= Organization::withTrashed()->where('slug', $this->route('organization'))->firstOrFail();
    }
}
