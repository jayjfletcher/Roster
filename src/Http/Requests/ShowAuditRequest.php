<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\ShowAuditEntryAction;
use JayI\Roster\Http\Request;
use JayI\Roster\Http\Resources\AuditEntryResource;
use JayI\Roster\Models\AuditEntry;
use JayI\Roster\Models\Organization;
use JayI\Roster\Support\AuditAccess;

final class ShowAuditRequest extends Request
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

    public function rules(): array
    {
        return ShowAuditEntryAction::rules();
    }

    public function persist(): JsonResponse
    {
        return (new AuditEntryResource(app(ShowAuditEntryAction::class)->execute($this->entry())))->response();
    }

    private function entry(): AuditEntry
    {
        return $this->resolvedEntry ??= AuditEntry::query()->whereKey($this->route('entry'))->firstOrFail();
    }
}
