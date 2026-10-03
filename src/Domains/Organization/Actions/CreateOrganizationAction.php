<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Domains\Organization\Concerns\ManagesMemberships;
use JayI\Roster\Domains\Organization\Concerns\OrganizationRules;
use JayI\Roster\Domains\Organization\Enums\MembershipSource;
use JayI\Roster\Domains\Organization\Events\OrganizationCreatedActionEvent;
use JayI\Roster\Domains\Organization\Events\OrganizationCreatingActionEvent;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\User\Enums\UserStatus;
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
            'owner' => ['sometimes', 'nullable', Rule::exists($users->table(), $users->routeKeyName())],
        ] + self::settingsRules();
    }

    /**
     * Create an organization; its owner, if any, becomes its first member.
     *
     * `owner` is the owning user's route key. Pass a user model as `$owner`
     * from code to skip the lookup. Without either the organization has no
     * owner until ownership is transferred to a member (as with
     * organizations synced from external systems); personal organizations
     * always need one.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?Model $owner = null, bool $personal = false): OrganizationModel
    {
        OrganizationCreatingActionEvent::dispatch($data);

        if ($owner === null && ($data['owner'] ?? null) !== null) {
            $owner = $this->users->findOrFail($data['owner']);
        }

        if ($owner === null && $personal) {
            throw ValidationException::withMessages(['owner' => __('roster::roster.personal_needs_owner')]);
        }

        $organization = DB::transaction(function () use ($data, $owner, $personal): OrganizationModel {
            $name = (string) $data['name'];
            $slug = is_string($data['slug'] ?? null) && $data['slug'] !== ''
                ? $data['slug']
                : Slugs::unique($name, OrganizationModel::query());

            $organization = OrganizationModel::query()->create([
                'name' => $name,
                'slug' => $slug,
                'owner_id' => $owner?->getKey(),
                'personal' => $personal,
                'auto_join' => (bool) ($data['auto_join'] ?? false),
                'provisioned_status' => (string) ($data['provisioned_status'] ?? UserStatus::Active->value),
            ]);

            $this->syncDomains($organization, (array) ($data['domains'] ?? []));

            if ($owner !== null) {
                $this->join($organization, $owner, $personal ? MembershipSource::Personal : MembershipSource::Direct);
            }

            return $organization->load(['domains', 'links'])->loadCount(['memberships', 'teams']);
        });

        OrganizationCreatedActionEvent::dispatch($organization);

        return $organization;
    }
}
