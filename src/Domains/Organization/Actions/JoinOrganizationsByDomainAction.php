<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use JayI\Roster\Domains\Organization\Concerns\ManagesMemberships;
use JayI\Roster\Domains\Organization\Enums\MembershipSource;
use JayI\Roster\Domains\Organization\Events\DomainJoinedActionEvent;
use JayI\Roster\Domains\Organization\Events\DomainJoiningActionEvent;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Support\Users;

final class JoinOrganizationsByDomainAction
{
    use ManagesMemberships;

    public function __construct(private readonly Users $users) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    /**
     * Join the user to every auto-join organization that owns their email's
     * domain, returning the organizations newly joined.
     *
     * Users whose email is not verified join nothing: anyone can type an
     * address on someone else's domain.
     *
     * @return Collection<int, OrganizationModel>
     */
    public function execute(Model $user): Collection
    {
        $email = $this->users->email($user);

        if ($email === null || ! str_contains($email, '@') || ! $this->users->emailVerified($user)) {
            return new Collection;
        }

        DomainJoiningActionEvent::dispatch($user);

        $domain = strtolower(substr($email, strrpos($email, '@') + 1));

        $joined = DB::transaction(function () use ($user, $domain): Collection {
            $organizations = OrganizationModel::query()
                ->where('auto_join', true)
                ->whereHas('domains', fn (Builder $query): Builder => $query->where('domain', $domain))
                ->whereDoesntHave('memberships', fn (Builder $query): Builder => $query->where('user_id', $user->getKey()))
                ->get();

            foreach ($organizations as $organization) {
                $this->join($organization, $user, MembershipSource::Domain);
            }

            return $organizations;
        });

        $joined->load(['domains', 'links'])->loadCount(['memberships', 'teams']);

        DomainJoinedActionEvent::dispatch($user, $joined->pluck('slug')->all());

        return $joined;
    }
}
