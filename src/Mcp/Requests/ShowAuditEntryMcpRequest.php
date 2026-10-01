<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Actions\ShowAuditEntryAction;
use JayI\Roster\Http\Resources\AuditEntryResource;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Models\AuditEntry;
use JayI\Roster\Models\Organization;
use JayI\Roster\Support\AuditAccess;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class ShowAuditEntryMcpRequest extends Request
{
    private ?AuditEntry $resolvedEntry = null;

    protected function ability(): string
    {
        return 'roster.audit.view';
    }

    protected function scope(): ?Organization
    {
        return $this->entry()->organization;
    }

    protected function self(): ?Model
    {
        return AuditAccess::selfFor($this->entry(), $this->actor());
    }

    protected function rules(): array
    {
        return ShowAuditEntryAction::rules() + ['entry' => ['required', 'integer']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        return Response::structured(['data' => (new AuditEntryResource(app(ShowAuditEntryAction::class)->execute($this->entry())))->resolve()]);
    }

    private function entry(): AuditEntry
    {
        return $this->resolvedEntry ??= AuditEntry::query()->whereKey($this->get('entry'))->firstOrFail();
    }
}
