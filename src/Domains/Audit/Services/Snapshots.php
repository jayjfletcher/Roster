<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Audit\Services;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Role\Models\RoleAssignmentModel;
use JayI\Roster\Domains\Role\Models\RoleModel;
use JayI\Roster\Domains\User\Models\ProfileModel;
use JayI\Roster\Support\Users;

/**
 * Point-in-time copies of models for the audit log, and the diff between two.
 *
 * Secrets never leave this class: a redacted field is kept only as a hash, so
 * a change is still detected, and written to the log as `[redacted]`.
 */
final class Snapshots
{
    public const string REDACTED = '[redacted]';

    private const string SECRET = "\0secret:";

    /** @var array<int, string> */
    private const array ALWAYS_REDACTED = ['password', 'remember_token', 'token_hash', 'token', 'client_secret', 'private_key'];

    /** @var array<int, string> */
    private const array IGNORED = ['created_at', 'updated_at'];

    public function __construct(
        private readonly Users $users,
        private readonly Repository $config,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function of(Model $model): array
    {
        $values = $this->attributes($model);

        if (is_a($model, $this->users->model())) {
            $profile = ProfileModel::query()->where('user_id', $model->getKey())->first();

            foreach ($profile === null ? [] : $this->attributes($profile, ['id', 'user_id']) as $key => $value) {
                $values['profile.'.$key] = $value;
            }
        }

        if ($model instanceof RoleModel) {
            $values['permissions'] = $model->permissions()->orderBy('name')->pluck('name')->all();
        }

        if ($model instanceof OrganizationModel) {
            $values['domains'] = $model->domains()->orderBy('domain')->pluck('domain')->map(fn (mixed $d): string => (string) $d)->all();
        }

        if ($model instanceof RoleAssignmentModel) {
            $values['role'] = $model->role?->slug;
            $values['organization'] = $model->organization?->slug;
            $values['team'] = $model->team?->slug;
        }

        return $values;
    }

    /**
     * Field changes between two snapshots, as `field => [old, new]`.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public function diff(array $before, array $after): array
    {
        $changes = [];

        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $key) {
            $old = $before[$key] ?? null;
            $new = $after[$key] ?? null;

            if ($old !== $new) {
                $changes[$key] = [$this->reveal($old), $this->reveal($new)];
            }
        }

        ksort($changes);

        return $changes;
    }

    /**
     * Redact secret keys anywhere in host-supplied data.
     *
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    public function redact(array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_string($key) && $this->isSecret($key)) {
                $values[$key] = is_array($value) ? array_map(fn (): string => self::REDACTED, $value) : self::REDACTED;
            } elseif (is_array($value)) {
                $values[$key] = $this->redact($value);
            }
        }

        return $values;
    }

    public function isSecret(string $key): bool
    {
        $field = str_contains($key, '.') ? substr($key, (int) strrpos($key, '.') + 1) : $key;

        $secrets = array_merge(
            self::ALWAYS_REDACTED,
            array_filter([$this->users->column('password')]),
            array_map('strval', (array) $this->config->get('roster.audit.redact', [])),
        );

        return in_array($field, $secrets, true) || in_array($key, $secrets, true);
    }

    /**
     * @param  array<int, string>  $skip
     * @return array<string, mixed>
     */
    private function attributes(Model $model, array $skip = []): array
    {
        $values = [];

        foreach (array_keys($model->getAttributes()) as $key) {
            if (in_array($key, [...self::IGNORED, ...$skip], true)) {
                continue;
            }

            $raw = $model->getAttribute($key);

            $values[$key] = match (true) {
                $this->isSecret($key) => $raw === null ? null : self::SECRET.hash('sha256', is_scalar($raw) ? (string) $raw : (string) json_encode($raw)),
                // Secrets nested in arrays (e.g. encrypted connection config)
                // are tracked by hash too, so changes show without values.
                is_array($raw) => $this->hashSecrets($this->normalize($raw)),
                default => $this->normalize($raw),
            };
        }

        return $values;
    }

    private function hashSecrets(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = is_string($key) && $this->isSecret($key) && $item !== null
                ? self::SECRET.hash('sha256', is_scalar($item) ? (string) $item : (string) json_encode($item))
                : $this->hashSecrets($item);
        }

        return $value;
    }

    private function normalize(mixed $value): mixed
    {
        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof DateTimeInterface => $value->format(DateTimeInterface::ATOM),
            is_array($value) => array_map(fn (mixed $item): mixed => $this->normalize($item), $value),
            is_object($value) => (string) json_encode($value),
            default => $value,
        };
    }

    private function reveal(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->reveal($item), $value);
        }

        return is_string($value) && str_starts_with($value, self::SECRET) ? self::REDACTED : $value;
    }
}
