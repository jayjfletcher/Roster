<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Audit\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Audit\Actions\RecordAuditEventAction;
use JayI\Roster\Domains\Audit\Resources\AuditEntryResource;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Http\Request;
use JayI\Roster\Support\Scopes;

final class StoreAuditRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.audit.record';
    }

    protected function scope(): OrganizationModel|TeamModel|null
    {
        return Scopes::fromInput($this->input('organization'));
    }

    public function rules(): array
    {
        return RecordAuditEventAction::rules();
    }

    public function persist(): JsonResponse
    {
        $entry = app(RecordAuditEventAction::class)->execute($this->validated(), $this->actor());

        return (new AuditEntryResource($entry->load(['actor', 'organization'])))->response()->setStatusCode(201);
    }
}
