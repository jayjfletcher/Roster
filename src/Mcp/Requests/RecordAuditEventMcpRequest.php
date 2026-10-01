<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\RecordAuditEventAction;
use JayI\Roster\Http\Resources\AuditEntryResource;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;
use JayI\Roster\Support\Scopes;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class RecordAuditEventMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.audit.record';
    }

    protected function scope(): Organization|Team|null
    {
        return Scopes::fromInput($this->get('organization'));
    }

    protected function rules(): array
    {
        return RecordAuditEventAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $entry = app(RecordAuditEventAction::class)->execute($validated, $this->actor());

        return Response::structured(['data' => (new AuditEntryResource($entry->load(['actor', 'organization'])))->resolve()]);
    }
}
