<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\SyncOrganizationsAction;
use JayI\Roster\Http\Request;
use JayI\Roster\Http\Resources\OrganizationSyncResults;

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
