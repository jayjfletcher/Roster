<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Events\Action\ScimTokenCreatedActionEvent;
use JayI\Roster\Events\Action\ScimTokenCreatingActionEvent;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\ScimToken;
use JayI\Roster\Models\SsoConnection;
use JayI\Roster\Scim\IssuedScimToken;

final class CreateScimTokenAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'expires_in_days' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:3650'],
            'sso_connection' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * Issue a token an identity provider uses to provision the organization.
     * With `sso_connection`, provisioned users' externalIds become that
     * connection's SSO subjects.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Organization $organization, array $data, ?Model $actor = null): IssuedScimToken
    {
        $connection = null;

        if (is_string($data['sso_connection'] ?? null) && $data['sso_connection'] !== '') {
            $connection = SsoConnection::query()
                ->where('organization_id', $organization->getKey())
                ->where('slug', $data['sso_connection'])
                ->first() ?? throw ValidationException::withMessages(['sso_connection' => __('roster::roster.unknown_sso_connection')]);
        }

        ScimTokenCreatingActionEvent::dispatch($organization, $data);

        $plain = 'scim_'.Str::random(48);

        $token = DB::transaction(fn (): ScimToken => ScimToken::query()->create([
            'organization_id' => $organization->getKey(),
            'name' => $data['name'],
            'token_hash' => hash('sha256', $plain),
            'sso_connection_id' => $connection?->getKey(),
            'created_by' => $actor?->getKey(),
            'expires_at' => is_numeric($data['expires_in_days'] ?? null) ? now()->addDays((int) $data['expires_in_days']) : null,
        ]));

        $token->load(['organization', 'ssoConnection']);

        ScimTokenCreatedActionEvent::dispatch($token);

        return new IssuedScimToken($token, $plain);
    }
}
