<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Transfer\Actions\ListTransfersAction;
use RefactorCircus\Roster\Domains\Transfer\Resources\TransferResource;
use RefactorCircus\Roster\Mcp\Request;

/**
 * Everyone may list their own transfers. An organization's transfers need
 * `roster.members.view` there; everyone's need `roster.users.view`.
 */
final class ListTransfersMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.members.view';
    }

    protected function scope(): ?OrganizationModel
    {
        $slug = $this->get('organization');

        return is_string($slug) && $slug !== '' ? OrganizationModel::query()->where('slug', $slug)->first() : null;
    }

    protected function self(): ?Model
    {
        return $this->scope() === null ? $this->actor() : null;
    }

    protected function rules(): array
    {
        return ListTransfersAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $actor = $this->actor();
        $everyone = $this->scope() !== null || app(Authorizer::class)->check($actor, 'roster.users.view');
        // Not everyone's: their own, plus those of the organizations they may see transfers in.
        $organizations = $everyone ? [] : (app(Authorizer::class)->organizationsWith($actor, 'roster.members.view') ?? []);
        $transfers = app(ListTransfersAction::class)->execute($validated, $everyone ? null : $actor, $organizations);

        return $this->structuredCollection(TransferResource::collection($transfers->items())->resolve(), [
            'meta' => [
                'current_page' => $transfers->currentPage(),
                'last_page' => $transfers->lastPage(),
                'per_page' => $transfers->perPage(),
                'total' => $transfers->total(),
            ],
        ]);
    }
}
