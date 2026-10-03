<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Audit\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Audit\Actions\ListAuditEntriesAction;
use JayI\Roster\Domains\Audit\Resources\AuditEntryResource;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Http\Request;
use JayI\Roster\Support\Scopes;
use JayI\Roster\Support\Users;

final class IndexAuditRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.audit.view';
    }

    /**
     * With an organization filter, its admins may read its entries.
     */
    protected function scope(): OrganizationModel|TeamModel|null
    {
        return Scopes::fromInput($this->input('organization'));
    }

    /**
     * With a user filter naming yourself, you may read your own entries.
     */
    protected function self(): ?Model
    {
        $user = $this->input('user');

        return $user === null ? null : app(Users::class)->query()->where(app(Users::class)->routeKeyName(), $user)->first();
    }

    protected function acrossOrganizations(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ListAuditEntriesAction::rules();
    }

    public function persist(): JsonResponse
    {
        return AuditEntryResource::collection(app(ListAuditEntriesAction::class)->execute($this->validated(), $this->organizations()))->response();
    }
}
