<?php

declare(strict_types=1);

namespace JayI\Roster\Audit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JayI\Roster\Contracts\ActionFinishedEvent;
use JayI\Roster\Contracts\ActionStartingEvent;
use JayI\Roster\Impersonation\ImpersonationContext;
use JayI\Roster\Models\AuditEntry;
use JayI\Roster\Models\Impersonation;
use JayI\Roster\Models\Invitation;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Role;
use JayI\Roster\Models\RoleAssignment;
use JayI\Roster\Models\Team;
use JayI\Roster\Models\Transfer;
use JayI\Roster\Scim\ScimContext;
use JayI\Roster\Support\Users;
use JayI\Roster\Transfers\TransferContext;
use ReflectionObject;
use ReflectionProperty;

/**
 * Turns Roster's Action events into audit entries.
 *
 * The starting event snapshots the models an Action is about to touch; the
 * finished event diffs them against their new state and appends the entry.
 * Reads (`*Listed`, `*Shown`) are not recorded.
 *
 * Bound per request: snapshots must not outlive it.
 */
final class AuditRecorder
{
    /** @var array<int, string> */
    private const array READ_VERBS = ['Listed', 'Shown', 'Listing', 'Showing'];

    /** @var array<string, array<string, mixed>> */
    private array $snapshots = [];

    public function __construct(
        private readonly Snapshots $snapshotter,
        private readonly AuditLog $log,
        private readonly Surface $surface,
        private readonly Users $users,
    ) {}

    public function starting(ActionStartingEvent $event): void
    {
        if ($this->isRead($event)) {
            return;
        }

        foreach ($this->models($event) as $model) {
            if ($model->exists) {
                $this->snapshots[$this->key($model)] ??= $this->snapshotter->of($model);
            }
        }
    }

    public function finished(ActionFinishedEvent $event): void
    {
        if ($this->isRead($event)) {
            return;
        }

        [$noun, $verb] = $this->name($event);
        $models = $this->models($event);
        $subject = $this->subject($models);
        $context = $this->context($event, $subject);

        $changes = $subject === null ? [] : $this->changes($subject, $verb);
        $surface = $this->surface->current();
        $request = in_array($surface, ['http', 'mcp', 'atrium', 'web'], true) ? $this->surface->request() : null;
        $actor = $this->surface->actor();

        $this->log->append([
            'source' => AuditEntry::SOURCE_ROSTER,
            'action' => $noun.'.'.$verb,
            'actor_id' => $actor instanceof Model ? $actor->getKey() : null,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject === null ? null : (string) $subject->getKey(),
            'subject_label' => $subject === null ? null : $this->label($subject),
            'organization_id' => $this->organizationId($models, $subject),
            'surface' => $surface,
            'ip' => $request?->ip(),
            'user_agent' => $request === null ? null : Str::limit((string) $request->userAgent(), 500, ''),
            'changes' => $changes,
            'context' => $this->snapshotter->redact($this->withImpersonator($context)),
        ]);
    }

    /**
     * A create lists every field as new and a delete every field as gone;
     * anything else diffs against the snapshot taken when the Action started.
     * Models the Action did not change (no snapshot) record no field changes.
     *
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    private function changes(Model $subject, string $verb): array
    {
        $key = $this->key($subject);
        $before = $this->snapshots[$key] ?? null;
        unset($this->snapshots[$key]);

        return match (true) {
            $verb === 'created' => $this->snapshotter->diff([], $this->snapshotter->of($subject)),
            $verb === 'deleted' => $before === null ? [] : $this->snapshotter->diff($before, []),
            $before === null => [],
            default => $this->snapshotter->diff($before, $this->snapshotter->of($subject->fresh() ?? $subject)),
        };
    }

    public function label(Model $model): string
    {
        return match (true) {
            is_a($model, $this->users->model()) => $this->users->name($model) ?? $this->users->email($model) ?? (string) $model->getKey(),
            $model instanceof Organization, $model instanceof Team, $model instanceof Role => $model->name,
            $model instanceof Invitation => $model->email,
            $model instanceof RoleAssignment => (string) $model->role?->name,
            $model instanceof Transfer => $model->type->label(),
            default => (string) ($model->getAttribute('name') ?? $model->getKey()),
        };
    }

    private function isRead(object $event): bool
    {
        // Recording an app event writes its own entry; logging that write
        // again would duplicate it.
        if (str_starts_with(class_basename($event), 'AuditEvent')) {
            return true;
        }

        foreach (self::READ_VERBS as $verb) {
            if (str_ends_with(class_basename($event), $verb.'ActionEvent')) {
                return true;
            }
        }

        return false;
    }

    /**
     * `UserSuspendedActionEvent` => ['user', 'suspended'];
     * `TeamMemberAddedActionEvent` => ['team_member', 'added'].
     *
     * @return array{0: string, 1: string}
     */
    private function name(object $event): array
    {
        $words = explode(' ', Str::headline(Str::beforeLast(class_basename($event), 'ActionEvent')));
        $verb = strtolower((string) array_pop($words));

        return [Str::snake(implode('', $words)), $verb];
    }

    /**
     * @return array<string, Model>
     */
    private function models(object $event): array
    {
        $models = [];

        foreach ((new ReflectionObject($event))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $value = $property->getValue($event);

            if ($value instanceof Model) {
                $models[$property->getName()] = $value;
            }
        }

        return $models;
    }

    /**
     * The entry is about a user when one is involved: joining a team, being
     * given a role. Otherwise it is about the event's first model.
     *
     * @param  array<string, Model>  $models
     */
    private function subject(array $models): ?Model
    {
        if (isset($models['assignment']) && $models['assignment'] instanceof RoleAssignment && $models['assignment']->user instanceof Model) {
            return $models['assignment']->user;
        }

        if (isset($models['impersonation']) && $models['impersonation'] instanceof Impersonation && $models['impersonation']->user instanceof Model) {
            return $models['impersonation']->user;
        }

        return $models['user'] ?? ($models === [] ? null : reset($models));
    }

    /**
     * @param  array<string, Model>  $models
     */
    private function organizationId(array $models, ?Model $subject): ?string
    {
        foreach ($models as $model) {
            $id = match (true) {
                $model instanceof Organization => $model->getKey(),
                $model instanceof Team, $model instanceof Invitation, $model instanceof RoleAssignment, $model instanceof Role, $model instanceof Impersonation => $model->getAttribute('organization_id'),
                default => null,
            };

            if (is_string($id)) {
                return $id;
            }
        }

        $id = $subject?->getAttribute('organization_id');

        return is_string($id) ? $id : null;
    }

    /**
     * The event's other models and scalar values, by property name.
     *
     * @return array<string, mixed>
     */
    private function context(object $event, ?Model $subject): array
    {
        $context = [];

        foreach ((new ReflectionObject($event))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $value = $property->getValue($event);
            $name = $property->getName();

            if ($value instanceof Model) {
                if ($subject !== null && $value->is($subject)) {
                    continue;
                }

                $context[$name] = match (true) {
                    $value instanceof RoleAssignment => ['role' => $value->role?->slug, 'organization' => $value->organization?->slug, 'team' => $value->team?->slug],
                    $value instanceof Impersonation => [
                        'id' => $value->id,
                        'reason' => $value->reason,
                        'by' => $value->impersonator instanceof Model ? $this->label($value->impersonator) : null,
                        'by_id' => (string) $value->impersonator_id,
                        'ended' => $value->end_reason,
                    ],
                    default => ['id' => (string) $value->getKey(), 'label' => $this->label($value)],
                };
            } elseif (is_scalar($value) || is_array($value)) {
                $context[$name] = $value;
            }
        }

        return $context;
    }

    /**
     * Note who was really acting when the change came from an impersonated
     * session.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function withImpersonator(array $context): array
    {
        $impersonator = app(ImpersonationContext::class)->impersonator();

        if ($impersonator !== null) {
            $context['impersonator'] = ['id' => (string) $impersonator->getKey(), 'label' => $this->label($impersonator)];
        }

        $transfer = app(TransferContext::class)->transfer;

        if ($transfer !== null) {
            $context['transfer'] = ['id' => $transfer->id, 'type' => $transfer->type->value];
        }

        $scim = app(ScimContext::class)->token;

        if ($scim !== null) {
            $context['scim_token'] = ['id' => $scim->id, 'name' => $scim->name];
        }

        return $context;
    }

    private function key(Model $model): string
    {
        return $model::class.':'.$model->getKey();
    }
}
