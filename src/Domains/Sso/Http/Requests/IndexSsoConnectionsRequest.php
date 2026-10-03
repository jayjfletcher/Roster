<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Organization\Http\Requests\OrganizationRequest;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Sso\Actions\ListSsoConnectionsAction;
use JayI\Roster\Domains\Sso\Resources\SsoConnectionResource;

final class IndexSsoConnectionsRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.sso.view';
    }

    protected function scope(): OrganizationModel
    {
        return $this->organization();
    }

    public function rules(): array
    {
        return ListSsoConnectionsAction::rules();
    }

    public function persist(): JsonResponse
    {
        $filters = ['organization' => $this->organization()->slug] + $this->validated();

        return SsoConnectionResource::collection(app(ListSsoConnectionsAction::class)->execute($filters))->response();
    }
}
