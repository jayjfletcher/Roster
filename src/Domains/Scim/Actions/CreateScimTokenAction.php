<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Scim\Data\IssuedScimToken;
use RefactorCircus\Roster\Domains\Scim\Events\ScimTokenCreatedActionEvent;
use RefactorCircus\Roster\Domains\Scim\Events\ScimTokenCreatingActionEvent;
use RefactorCircus\Roster\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;

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
    public function execute(OrganizationModel $organization, array $data, ?Model $actor = null): IssuedScimToken
    {
        $connection = null;

        if (is_string($data['sso_connection'] ?? null) && $data['sso_connection'] !== '') {
            $connection = SsoConnectionModel::query()
                ->where('organization_id', $organization->getKey())
                ->where('slug', $data['sso_connection'])
                ->first() ?? throw ValidationException::withMessages(['sso_connection' => __('roster::roster.unknown_sso_connection')]);
        }

        ScimTokenCreatingActionEvent::dispatch($organization, $data);

        $plain = 'scim_'.Str::random(48);

        $token = DB::transaction(fn (): ScimTokenModel => ScimTokenModel::query()->create([
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
