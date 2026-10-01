<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use JayI\Roster\Actions\Concerns\ManagesMemberships;
use JayI\Roster\Actions\Concerns\OrganizationRules;
use JayI\Roster\Enums\MembershipSource;
use JayI\Roster\Events\Action\OrganizationCreatedActionEvent;
use JayI\Roster\Events\Action\OrganizationCreatingActionEvent;
use JayI\Roster\Models\Organization;
use JayI\Roster\Support\Slugs;
use JayI\Roster\Support\Users;

final class CreateOrganizationAction
{
    use ManagesMemberships;
    use OrganizationRules;

    public function __construct(private readonly Users $users) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        $users = app(Users::class);

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'alpha_dash', 'max:255', Rule::unique('roster_organizations', 'slug')],
            'owner' => ['required', Rule::exists($users->table(), $users->routeKeyName())],
        ] + self::settingsRules();
    }

    /**
     * Create an organization; its owner becomes its first member.
     *
     * `owner` is the owning user's route key. Pass a user model as `$owner`
     * from code to skip the lookup.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?Model $owner = null, bool $personal = false): Organization
    {
        OrganizationCreatingActionEvent::dispatch($data);

        $owner ??= $this->users->findOrFail($data['owner'] ?? null);

        $organization = DB::transaction(function () use ($data, $owner, $personal): Organization {
            $name = (string) $data['name'];
            $slug = is_string($data['slug'] ?? null) && $data['slug'] !== ''
                ? $data['slug']
                : Slugs::unique($name, Organization::query());

            $organization = Organization::query()->create([
                'name' => $name,
                'slug' => $slug,
                'owner_id' => $owner->getKey(),
                'personal' => $personal,
                'auto_join' => (bool) ($data['auto_join'] ?? false),
            ]);

            $this->syncDomains($organization, (array) ($data['domains'] ?? []));
            $this->join($organization, $owner, $personal ? MembershipSource::Personal : MembershipSource::Direct);

            return $organization->load('domains')->loadCount(['memberships', 'teams']);
        });

        OrganizationCreatedActionEvent::dispatch($organization);

        return $organization;
    }
}
