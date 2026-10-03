<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Scim\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Scim\Actions\RevokeScimTokenAction;
use JayI\Roster\Domains\Scim\Models\ScimTokenModel;
use JayI\Roster\Domains\Scim\Resources\ScimTokenResource;
use JayI\Roster\Http\Request;

final class RevokeScimTokenRequest extends Request
{
    private ?ScimTokenModel $resolved = null;

    protected function ability(): string
    {
        return 'roster.scim.manage';
    }

    protected function scope(): ?OrganizationModel
    {
        return $this->token()->organization;
    }

    public function rules(): array
    {
        return RevokeScimTokenAction::rules();
    }

    public function persist(): JsonResponse
    {
        return (new ScimTokenResource(app(RevokeScimTokenAction::class)->execute($this->token())))->response();
    }

    private function token(): ScimTokenModel
    {
        return $this->resolved ??= ScimTokenModel::query()->whereKey($this->route('token'))->firstOrFail();
    }
}
