<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\ListSsoConnectionsAction;
use JayI\Roster\Http\Resources\SsoConnectionResource;
use JayI\Roster\Models\Organization;

final class IndexSsoConnectionsRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.sso.view';
    }

    protected function scope(): Organization
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
