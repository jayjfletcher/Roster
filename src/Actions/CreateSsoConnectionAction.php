<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use JayI\Roster\Actions\Concerns\SsoConnectionRules;
use JayI\Roster\Events\Action\SsoConnectionCreatedActionEvent;
use JayI\Roster\Events\Action\SsoConnectionCreatingActionEvent;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\SsoConnection;
use JayI\Roster\Support\Slugs;

final class CreateSsoConnectionAction
{
    use SsoConnectionRules;

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'alpha_dash', 'max:255', Rule::unique('roster_sso_connections', 'slug')],
            'protocol' => ['required', Rule::in(SsoConnection::PROTOCOLS)],
        ] + self::settingRules();
    }

    /**
     * Connect an organization to its identity provider.
     *
     * OIDC needs `issuer`, `client_id`, `client_secret`; Microsoft Entra ID
     * (`azure`) needs `tenant`, `client_id`, `client_secret`; SAML needs
     * `metadata_url`, or `entity_id`, `sso_url` and `certificate`.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Organization $organization, array $data): SsoConnection
    {
        $protocol = (string) $data['protocol'];
        $settings = $this->settings($protocol, $data);

        SsoConnectionCreatingActionEvent::dispatch($organization, $data);

        $connection = DB::transaction(fn (): SsoConnection => SsoConnection::query()->create([
            'organization_id' => $organization->getKey(),
            'name' => $data['name'],
            'slug' => is_string($data['slug'] ?? null) && $data['slug'] !== ''
                ? $data['slug']
                : Slugs::unique($organization->slug.'-'.$data['name'], SsoConnection::query()),
            'protocol' => $protocol,
            'config' => $settings,
            'jit' => (bool) ($data['jit'] ?? true),
            'enforced' => (bool) ($data['enforced'] ?? false),
            'enabled' => (bool) ($data['enabled'] ?? true),
        ]));

        $connection->load('organization')->loadCount('identities');

        SsoConnectionCreatedActionEvent::dispatch($connection);

        return $connection;
    }
}
