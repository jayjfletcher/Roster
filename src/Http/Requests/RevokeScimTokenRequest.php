<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\RevokeScimTokenAction;
use JayI\Roster\Http\Request;
use JayI\Roster\Http\Resources\ScimTokenResource;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\ScimToken;

final class RevokeScimTokenRequest extends Request
{
    private ?ScimToken $resolved = null;

    protected function ability(): string
    {
        return 'roster.scim.manage';
    }

    protected function scope(): ?Organization
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

    private function token(): ScimToken
    {
        return $this->resolved ??= ScimToken::query()->whereKey($this->route('token'))->firstOrFail();
    }
}
