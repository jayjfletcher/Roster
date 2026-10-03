<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Audit\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use JayI\Roster\Domains\Audit\Exceptions\AuditLogIsAppendOnlyException;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Support\Users;

/**
 * One entry in the append-only, hash-chained audit log.
 *
 * @property int $id
 * @property string $source
 * @property string $action
 * @property int|string|null $actor_id
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property string|null $subject_label
 * @property string|null $organization_id
 * @property string $surface
 * @property string|null $ip
 * @property string|null $user_agent
 * @property array<string, array{0: mixed, 1: mixed}>|null $changes
 * @property array<string, mixed>|null $context
 * @property string|null $previous_hash
 * @property string $hash
 * @property Carbon $created_at
 */
final class AuditEntryModel extends Model
{
    public const string SOURCE_ROSTER = 'roster';

    public const string SOURCE_APP = 'app';

    public const UPDATED_AT = null;

    protected $table = 'roster_audit_entries';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        $refuse = function (self $entry): never {
            throw AuditLogIsAppendOnlyException::forEntry($entry->getKey());
        };

        self::updating($refuse);
        self::deleting($refuse);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(app(Users::class)->model(), 'actor_id');
    }

    /**
     * @return BelongsTo<OrganizationModel, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    /**
     * Entries about a user or made by them.
     *
     * @param  Builder<self>  $query
     */
    public function scopeAbout(Builder $query, Model $user): void
    {
        $query->where(fn (Builder $about): Builder => $about
            ->where('actor_id', $user->getKey())
            ->orWhere(fn (Builder $subject): Builder => $subject
                ->where('subject_type', $user->getMorphClass())
                ->where('subject_id', (string) $user->getKey())));
    }

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
