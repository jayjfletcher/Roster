<?php

declare(strict_types=1);

namespace JayI\Roster\Transfers\Planners;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Actions\SyncOrganizationAction;
use JayI\Roster\Models\Transfer;
use JayI\Roster\Support\OrganizationSyncResult;
use JayI\Roster\Support\Users;

/**
 * Organizations from an external system:
 * `source, external_id, name, account_number, slug, domains, owner` (email).
 * Each row is a sync record; blank cells leave the field as it is.
 */
final class OrganizationsPlanner extends Planner
{
    public function __construct(private readonly Users $users) {}

    public function plan(array $values, Transfer $transfer, ?Model $actor): array
    {
        try {
            $outcome = app(SyncOrganizationAction::class)->preview($this->record($values));
        } catch (ValidationException $exception) {
            return $this->outcome(self::ERROR, ...Arr::flatten($exception->errors()));
        }

        return match ($outcome) {
            OrganizationSyncResult::CREATED => $this->outcome(self::CREATE, __('roster::roster.import_new_organization')),
            OrganizationSyncResult::UPDATED => $this->outcome(self::UPDATE, __('roster::roster.import_updates_organization')),
            default => $this->outcome(self::SKIP, __('roster::roster.import_organization_unchanged')),
        };
    }

    public function apply(array $values, Transfer $transfer, ?Model $actor): array
    {
        $plan = $this->plan($values, $transfer, $actor);

        if (in_array($plan['action'], [self::ERROR, self::SKIP], true)) {
            return $plan;
        }

        app(SyncOrganizationAction::class)->execute($this->record($values));

        return $plan;
    }

    /**
     * The sync record for a row: blank cells are left out, `domains` is a
     * list, and the owner's email becomes their user key.
     *
     * @param  array<string, string>  $values
     * @return array<string, mixed>
     */
    private function record(array $values): array
    {
        $record = array_filter($values, fn (string $value): bool => $value !== '');

        if (isset($values['owner']) && $values['owner'] !== '') {
            $record['owner'] = $this->owner($values['owner']);
        }

        if (isset($values['domains']) && $values['domains'] !== '') {
            $record['domains'] = $this->list($values['domains']);
        }

        return $record;
    }

    private function owner(string $email): string
    {
        $column = $this->users->column('email');
        $user = $column === null ? null : $this->users->query()->whereLike($column, $email)->get()
            ->first(fn (Model $user): bool => strcasecmp((string) $this->users->email($user), $email) === 0);

        if ($user === null) {
            throw ValidationException::withMessages(['owner' => __('roster::roster.import_unknown_owner', ['email' => $email])]);
        }

        return (string) $user->getRouteKey();
    }
}
