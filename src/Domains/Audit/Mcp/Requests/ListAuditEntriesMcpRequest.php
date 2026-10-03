<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Audit\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Domains\Audit\Actions\ListAuditEntriesAction;
use JayI\Roster\Domains\Audit\Resources\AuditEntryResource;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Support\Scopes;
use JayI\Roster\Support\Users;
use Laravel\Mcp\ResponseFactory;

final class ListAuditEntriesMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.audit.view';
    }

    protected function scope(): OrganizationModel|TeamModel|null
    {
        return Scopes::fromInput($this->get('organization'));
    }

    protected function self(): ?Model
    {
        $user = $this->get('user');

        return $user === null ? null : app(Users::class)->query()->where(app(Users::class)->routeKeyName(), $user)->first();
    }

    protected function acrossOrganizations(): bool
    {
        return true;
    }

    protected function rules(): array
    {
        return ListAuditEntriesAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $entries = app(ListAuditEntriesAction::class)->execute($validated, $this->organizations());

        return $this->structuredCollection(AuditEntryResource::collection($entries->items())->resolve(), [
            'meta' => [
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'per_page' => $entries->perPage(),
                'total' => $entries->total(),
            ],
        ]);
    }
}
