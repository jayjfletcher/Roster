<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\ListAuditEntriesAction;
use JayI\Roster\Http\Request;
use JayI\Roster\Http\Resources\AuditEntryResource;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;
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
    protected function scope(): Organization|Team|null
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

    public function rules(): array
    {
        return ListAuditEntriesAction::rules();
    }

    public function persist(): JsonResponse
    {
        return AuditEntryResource::collection(app(ListAuditEntriesAction::class)->execute($this->validated()))->response();
    }
}
