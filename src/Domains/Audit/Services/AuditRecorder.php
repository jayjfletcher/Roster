<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Audit\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JayI\Foundation\Contracts\ActionFinishedEvent;
use JayI\Foundation\Contracts\ActionStartingEvent;
use JayI\Foundation\Packages\PackageRegistry;
use JayI\Roster\Domains\Audit\Models\AuditEntryModel;
use JayI\Roster\Domains\Impersonation\Models\ImpersonationModel;
use JayI\Roster\Domains\Impersonation\Services\ImpersonationContext;
use JayI\Roster\Domains\Invitation\Models\InvitationModel;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Role\Models\RoleAssignmentModel;
use JayI\Roster\Domains\Role\Models\RoleModel;
use JayI\Roster\Domains\Scim\Services\ScimContext;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Domains\Transfer\Models\TransferModel;
use JayI\Roster\Domains\Transfer\Services\TransferContext;
use JayI\Roster\Support\Users;
use ReflectionObject;
use ReflectionProperty;

/**
 * Turns Roster's Action events into audit entries.
 *
 * The event contracts are jayi/foundation's, shared by every package of the
 * suite, so events from other packages are ignored: this log records Roster
 * alone until the audit log moves to jayi/keen.
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
        if (! $this->ownsEvent($event) || $this->isRead($event)) {
            return;
        }

        // Only the entry's subject gets field changes, and only when the
        // event is about its own fields: `member.added` changes nothing on
        // the user, so snapshotting them would be wasted queries.
        [$noun] = $this->name($event);
        $subject = $this->subject($this->models($event));

        if ($subject !== null && $subject->exists && $this->changesItself($subject, $noun)) {
            $this->snapshots[$this->key($subject)] ??= $this->snapshotter->of($subject);
        }
    }

    /**
     * Whether an event named `$noun` can change the subject's own fields.
     */
    private function changesItself(Model $subject, string $noun): bool
    {
        return is_a($subject, $this->users->model())
            ? in_array($noun, ['user', 'profile', 'context'], true)
            : Str::snake(Str::beforeLast(class_basename($subject), 'Model')) === $noun;
    }

    public function finished(ActionFinishedEvent $event): void
    {
        if (! $this->ownsEvent($event) || $this->isRead($event)) {
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
            'source' => AuditEntryModel::SOURCE_ROSTER,
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
            $model instanceof OrganizationModel, $model instanceof TeamModel, $model instanceof RoleModel => $model->name,
            $model instanceof InvitationModel => $model->email,
            $model instanceof RoleAssignmentModel => (string) $model->role?->name,
            $model instanceof TransferModel => $model->type->label(),
            default => (string) ($model->getAttribute('name') ?? $model->getKey()),
        };
    }

    /**
     * Whether the event is one of Roster's own, not another package's.
     */
    private function ownsEvent(object $event): bool
    {
        return app(PackageRegistry::class)->for($event)?->key === 'roster';
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
        if (isset($models['assignment']) && $models['assignment'] instanceof RoleAssignmentModel && $models['assignment']->user instanceof Model) {
            return $models['assignment']->user;
        }

        if (isset($models['impersonation']) && $models['impersonation'] instanceof ImpersonationModel && $models['impersonation']->user instanceof Model) {
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
                $model instanceof OrganizationModel => $model->getKey(),
                $model instanceof TeamModel, $model instanceof InvitationModel, $model instanceof RoleAssignmentModel, $model instanceof RoleModel, $model instanceof ImpersonationModel => $model->getAttribute('organization_id'),
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
                    $value instanceof RoleAssignmentModel => ['role' => $value->role?->slug, 'organization' => $value->organization?->slug, 'team' => $value->team?->slug],
                    $value instanceof ImpersonationModel => [
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
