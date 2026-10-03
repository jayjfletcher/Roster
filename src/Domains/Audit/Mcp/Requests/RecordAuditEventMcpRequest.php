<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Audit\Mcp\Requests;

use JayI\Roster\Domains\Audit\Actions\RecordAuditEventAction;
use JayI\Roster\Domains\Audit\Resources\AuditEntryResource;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Support\Scopes;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class RecordAuditEventMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.audit.record';
    }

    protected function scope(): OrganizationModel|TeamModel|null
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
