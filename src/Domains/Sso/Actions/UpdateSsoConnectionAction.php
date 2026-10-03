<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use JayI\Roster\Domains\Sso\Concerns\SsoConnectionRules;
use JayI\Roster\Domains\Sso\Events\SsoConnectionUpdatedActionEvent;
use JayI\Roster\Domains\Sso\Events\SsoConnectionUpdatingActionEvent;
use JayI\Roster\Domains\Sso\Models\SsoConnectionModel;

final class UpdateSsoConnectionAction
{
    use SsoConnectionRules;

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(?SsoConnectionModel $connection = null): array
    {
        $slug = Rule::unique('roster_sso_connections', 'slug');

        if ($connection !== null) {
            $slug->ignore($connection->getKey());
        }

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'alpha_dash', 'max:255', $slug],
        ] + self::settingRules();
    }

    /**
     * Change a connection. The protocol is fixed; a blank client secret
     * keeps the stored one.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(SsoConnectionModel $connection, array $data): SsoConnectionModel
    {
        $settings = $this->settings($connection->protocol, $data, $connection->config);

        SsoConnectionUpdatingActionEvent::dispatch($connection, $data);

        DB::transaction(fn () => $connection->update(
            array_intersect_key($data, array_flip(['name', 'slug', 'jit', 'enforced', 'enabled'])) + ['config' => $settings],
        ));

        $connection = $connection->refresh()->load('organization')->loadCount('identities');

        SsoConnectionUpdatedActionEvent::dispatch($connection);

        return $connection;
    }
}
