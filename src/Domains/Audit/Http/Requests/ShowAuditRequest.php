<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Audit\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Audit\Actions\ShowAuditEntryAction;
use JayI\Roster\Domains\Audit\Models\AuditEntryModel;
use JayI\Roster\Domains\Audit\Resources\AuditEntryResource;
use JayI\Roster\Domains\Audit\Services\AuditAccess;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Http\Request;

final class ShowAuditRequest extends Request
{
    private ?AuditEntryModel $resolvedEntry = null;

    protected function ability(): string
    {
        return 'roster.audit.view';
    }

    protected function scope(): ?OrganizationModel
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

    private function entry(): AuditEntryModel
    {
        return $this->resolvedEntry ??= AuditEntryModel::query()->whereKey($this->route('entry'))->firstOrFail();
    }
}
