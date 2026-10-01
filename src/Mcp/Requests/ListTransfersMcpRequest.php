<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Access\Authorizer;
use JayI\Roster\Actions\ListTransfersAction;
use JayI\Roster\Http\Resources\TransferResource;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Models\Organization;
use Laravel\Mcp\ResponseFactory;

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

    protected function scope(): ?Organization
    {
        $slug = $this->get('organization');

        return is_string($slug) && $slug !== '' ? Organization::query()->where('slug', $slug)->first() : null;
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
        $transfers = app(ListTransfersAction::class)->execute($validated, $everyone ? null : $actor);

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
