<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Organization\Actions\SyncOrganizationsAction;
use JayI\Roster\Domains\Organization\Resources\OrganizationSyncResults;
use JayI\Roster\Http\Request;

/**
 * Sync a batch of external records; each record succeeds or fails alone.
 */
final class SyncOrganizationsRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.organizations.sync';
    }

    public function rules(): array
    {
        return SyncOrganizationsAction::rules();
    }

    public function persist(): JsonResponse
    {
        return response()->json(['data' => OrganizationSyncResults::present(app(SyncOrganizationsAction::class)->execute($this->validated()))]);
    }
}
