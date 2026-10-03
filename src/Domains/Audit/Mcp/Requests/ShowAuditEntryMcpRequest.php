<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Audit\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Domains\Audit\Actions\ShowAuditEntryAction;
use JayI\Roster\Domains\Audit\Models\AuditEntryModel;
use JayI\Roster\Domains\Audit\Resources\AuditEntryResource;
use JayI\Roster\Domains\Audit\Services\AuditAccess;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class ShowAuditEntryMcpRequest extends Request
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

    protected function rules(): array
    {
        return ShowAuditEntryAction::rules() + ['entry' => ['required', 'integer']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        return Response::structured(['data' => (new AuditEntryResource(app(ShowAuditEntryAction::class)->execute($this->entry())))->resolve()]);
    }

    private function entry(): AuditEntryModel
    {
        return $this->resolvedEntry ??= AuditEntryModel::query()->whereKey($this->get('entry'))->firstOrFail();
    }
}
