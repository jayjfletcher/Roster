<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use JayI\Roster\Access\Authorizer;
use JayI\Roster\Actions\ListTransfersAction;
use JayI\Roster\Http\Request;
use JayI\Roster\Http\Resources\TransferResource;
use JayI\Roster\Models\Organization;

/**
 * Everyone may list their own transfers. An organization's transfers need
 * `roster.members.view` there; everyone's need `roster.users.view`.
 */
final class IndexTransfersRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.members.view';
    }

    protected function scope(): ?Organization
    {
        $slug = $this->input('organization');

        return is_string($slug) && $slug !== '' ? Organization::query()->where('slug', $slug)->first() : null;
    }

    protected function self(): ?Model
    {
        return $this->scope() === null ? $this->actor() : null;
    }

    public function rules(): array
    {
        return ListTransfersAction::rules();
    }

    public function persist(): JsonResponse
    {
        $actor = $this->actor();
        $everyone = $this->scope() !== null || app(Authorizer::class)->check($actor, 'roster.users.view');

        return TransferResource::collection(app(ListTransfersAction::class)->execute($this->validated(), $everyone ? null : $actor))->response();
    }
}
