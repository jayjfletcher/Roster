<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Permission\Services\Authorizer;
use JayI\Roster\Domains\Transfer\Actions\ListTransfersAction;
use JayI\Roster\Domains\Transfer\Resources\TransferResource;
use JayI\Roster\Http\Request;

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

    protected function scope(): ?OrganizationModel
    {
        $slug = $this->input('organization');

        return is_string($slug) && $slug !== '' ? OrganizationModel::query()->where('slug', $slug)->first() : null;
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

        // Not everyone's: their own, plus those of the organizations they may see transfers in.
        $organizations = $everyone ? [] : (app(Authorizer::class)->organizationsWith($actor, 'roster.members.view') ?? []);

        return TransferResource::collection(app(ListTransfersAction::class)->execute($this->validated(), $everyone ? null : $actor, $organizations))->response();
    }
}
