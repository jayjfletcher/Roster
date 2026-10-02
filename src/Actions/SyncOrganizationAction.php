<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Actions\Concerns\ManagesMemberships;
use JayI\Roster\Enums\MembershipSource;
use JayI\Roster\Events\Action\OrganizationSyncedActionEvent;
use JayI\Roster\Events\Action\OrganizationSyncingActionEvent;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\OrganizationLink;
use JayI\Roster\Support\OrganizationSyncResult;
use JayI\Roster\Support\Users;

final class SyncOrganizationAction
{
    use ManagesMemberships;

    /**
     * The organization fields a record may set; anything left out is kept.
     */
    private const array FIELDS = ['name', 'slug', 'domains', 'auto_join'];

    public function __construct(private readonly Users $users) {}

    /**
     * The record's shape. Uniqueness (slug, domains) is checked against the
     * organization the record resolves to, in `execute()`.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'source' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9][a-z0-9_.-]*$/'],
            'external_id' => ['required', 'string', 'max:191'],
            'account_number' => ['sometimes', 'nullable', 'string', 'max:191'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'alpha_dash', 'max:255'],
            'domains' => ['sometimes', 'array', 'max:50'],
            'domains.*' => ['string', 'distinct', 'max:253'],
            'auto_join' => ['sometimes', 'boolean'],
            'owner' => ['sometimes', 'nullable'],
            'organization' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * Create or update the organization behind an external record, matched
     * by `source` + `external_id`. On the first sync, `organization` (a slug)
     * links an existing organization instead of creating one.
     *
     * Only the fields present are written. `owner` (a user route key) is
     * applied when creating, or when the organization has no owner yet; an
     * existing owner is never replaced.
     *
     * `$fresh: false` skips reloading the organization's domains, links and
     * counts afterwards, for callers that only need the outcome (bulk sync).
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, bool $fresh = true): OrganizationSyncResult
    {
        $data = Validator::validate($data, self::rules());
        $source = (string) $data['source'];
        $externalId = (string) $data['external_id'];

        OrganizationSyncingActionEvent::dispatch($data);

        $result = DB::transaction(function () use ($data, $source, $externalId, $fresh): OrganizationSyncResult {
            $link = OrganizationLink::query()->where('source', $source)->where('external_id', $externalId)->first();
            $organization = $link !== null ? $link->organization : $this->existing($data, $source);

            [$organization, $outcome] = $organization === null
                ? [$this->create($data), OrganizationSyncResult::CREATED]
                : [$organization, $this->update($organization, $data) ? OrganizationSyncResult::UPDATED : OrganizationSyncResult::UNCHANGED];

            $link ??= new OrganizationLink(['organization_id' => $organization->getKey(), 'source' => $source, 'external_id' => $externalId]);

            if (array_key_exists('account_number', $data)) {
                $link->account_number = $data['account_number'] === null ? null : (string) $data['account_number'];
            }

            if ($outcome === OrganizationSyncResult::UNCHANGED && (! $link->exists || $link->isDirty('account_number'))) {
                $outcome = OrganizationSyncResult::UPDATED;
            }

            $link->synced_at = now();
            $link->save();

            if ($fresh) {
                $organization->refresh()->load(['domains', 'links'])->loadCount(['memberships', 'teams']);
            }

            return new OrganizationSyncResult($organization, $link, $outcome);
        });

        OrganizationSyncedActionEvent::dispatch($result->organization, $source, $externalId, $result->link->account_number, $result->outcome);

        return $result;
    }

    /**
     * What `execute()` would do with the record - created, updated or
     * unchanged - without writing anything or firing events. Throws the same
     * ValidationException it would. Used by the CSV import's preview.
     *
     * @param  array<string, mixed>  $data
     */
    public function preview(array $data): string
    {
        $data = Validator::validate($data, self::rules());
        $link = OrganizationLink::query()->where('source', $data['source'])->where('external_id', $data['external_id'])->first();
        $organization = $link !== null ? $link->organization : $this->existing($data, (string) $data['source']);

        if ($organization === null) {
            $fields = array_intersect_key($data, array_flip([...self::FIELDS, 'owner']));
            Validator::validate($fields, ['name' => ['required']] + CreateOrganizationAction::rules());

            return OrganizationSyncResult::CREATED;
        }

        $changed = $this->changes($organization, $data);

        if ($changed !== []) {
            Validator::validate($changed, UpdateOrganizationAction::rules($organization));
        }

        $owner = $data['owner'] ?? null;
        $claims = $owner !== null && $owner !== '' && $organization->owner_id === null;
        $account = array_key_exists('account_number', $data) && $data['account_number'] !== $link?->account_number;

        return $changed !== [] || $claims || $link === null || $account
            ? OrganizationSyncResult::UPDATED
            : OrganizationSyncResult::UNCHANGED;
    }

    /**
     * The organization named by `organization` on a first sync, if any.
     *
     * @param  array<string, mixed>  $data
     */
    private function existing(array $data, string $source): ?Organization
    {
        $slug = $data['organization'] ?? null;

        if (! is_string($slug) || $slug === '') {
            return null;
        }

        $organization = Organization::query()->where('slug', $slug)->first()
            ?? throw ValidationException::withMessages(['organization' => __('roster::roster.unknown_organization')]);

        if ($organization->links()->where('source', $source)->exists()) {
            throw ValidationException::withMessages(['organization' => __('roster::roster.already_linked', ['source' => $source])]);
        }

        return $organization;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function create(array $data): Organization
    {
        $fields = array_intersect_key($data, array_flip([...self::FIELDS, 'owner']));
        Validator::validate($fields, ['name' => ['required']] + CreateOrganizationAction::rules());

        return app(CreateOrganizationAction::class)->execute($fields);
    }

    /**
     * Write the record's fields where they differ; true when anything changed.
     *
     * @param  array<string, mixed>  $data
     */
    private function update(Organization $organization, array $data): bool
    {
        $changed = $this->changes($organization, $data);

        if ($changed !== []) {
            Validator::validate($changed, UpdateOrganizationAction::rules($organization));
            app(UpdateOrganizationAction::class)->execute($organization, $changed);
        }

        return $this->claimOwnership($organization, $data['owner'] ?? null) || $changed !== [];
    }

    /**
     * The record's fields that differ from the organization.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function changes(Organization $organization, array $data): array
    {
        $fields = array_intersect_key($data, array_flip(self::FIELDS));

        return array_filter($fields, fn (mixed $value, string $field): bool => $this->differs($organization, $field, $value), ARRAY_FILTER_USE_BOTH);
    }

    private function differs(Organization $organization, string $field, mixed $value): bool
    {
        return match ($field) {
            'domains' => $this->normalized((array) $value) !== $this->normalized($organization->loadMissing('domains')->domains->pluck('domain')->all()),
            'auto_join' => (bool) $value !== $organization->auto_join,
            'slug' => $value !== null && $value !== '' && $value !== $organization->slug,
            default => (string) $value !== (string) $organization->getAttribute($field),
        };
    }

    /**
     * @param  array<int, mixed>  $domains
     * @return array<int, string>
     */
    private function normalized(array $domains): array
    {
        $domains = array_values(array_unique(array_map(fn (mixed $domain): string => strtolower((string) $domain), $domains)));
        sort($domains);

        return $domains;
    }

    /**
     * Give an ownerless organization the record's owner, who joins it.
     */
    private function claimOwnership(Organization $organization, mixed $owner): bool
    {
        if ($owner === null || $owner === '' || $organization->owner_id !== null) {
            return false;
        }

        $user = $this->users->query()->where($this->users->routeKeyName(), $owner)->first()
            ?? throw ValidationException::withMessages(['owner' => __('validation.exists', ['attribute' => 'owner'])]);

        $organization->update(['owner_id' => $user->getKey()]);
        $this->join($organization, $user, MembershipSource::Direct);

        return true;
    }
}
