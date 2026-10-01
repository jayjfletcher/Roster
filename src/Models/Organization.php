<?php

declare(strict_types=1);

namespace JayI\Roster\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use JayI\Roster\Database\Factories\OrganizationFactory;
use JayI\Roster\Support\Users;

/**
 * The tenant: owns teams, memberships and invitations.
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property int|string $owner_id
 * @property bool $personal
 * @property bool $auto_join
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    use HasUlids;

    protected $table = 'roster_organizations';

    protected $fillable = ['name', 'slug', 'owner_id', 'personal', 'auto_join'];

    protected $attributes = [
        'personal' => false,
        'auto_join' => false,
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(app(Users::class)->model(), 'owner_id');
    }

    /**
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class, 'organization_id');
    }

    /**
     * @return BelongsToMany<Model, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(app(Users::class)->model(), 'roster_memberships', 'organization_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Team, $this>
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class, 'organization_id');
    }

    /**
     * @return HasMany<OrganizationDomain, $this>
     */
    public function domains(): HasMany
    {
        return $this->hasMany(OrganizationDomain::class, 'organization_id');
    }

    /**
     * @return HasMany<Invitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'organization_id');
    }

    public function isOwnedBy(Model $user): bool
    {
        return (string) $this->owner_id === (string) $user->getKey();
    }

    public function membershipFor(Model $user): ?Membership
    {
        return $this->memberships()->where('user_id', $user->getKey())->first();
    }

    protected static function newFactory(): OrganizationFactory
    {
        return OrganizationFactory::new();
    }

    protected function casts(): array
    {
        return [
            'personal' => 'boolean',
            'auto_join' => 'boolean',
        ];
    }
}
