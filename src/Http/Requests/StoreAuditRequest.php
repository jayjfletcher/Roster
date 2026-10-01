<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\RecordAuditEventAction;
use JayI\Roster\Http\Request;
use JayI\Roster\Http\Resources\AuditEntryResource;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;
use JayI\Roster\Support\Scopes;

final class StoreAuditRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.audit.record';
    }

    protected function scope(): Organization|Team|null
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
