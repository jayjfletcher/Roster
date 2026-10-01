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
use JayI\Roster\Database\Factories\TeamFactory;

/**
 * A group of organization members. Slugs are unique within the organization.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $name
 * @property string $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    use HasUlids;

    protected $table = 'roster_teams';

    protected $fillable = ['organization_id', 'name', 'slug'];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * @return HasMany<TeamMember, $this>
     */
    public function seats(): HasMany
    {
        return $this->hasMany(TeamMember::class, 'team_id');
    }

    /**
     * @return BelongsToMany<Membership, $this>
     */
    public function memberships(): BelongsToMany
    {
        return $this->belongsToMany(Membership::class, 'roster_team_members', 'team_id', 'membership_id')->withTimestamps();
    }

    public function hasMember(Model $user): bool
    {
        return $this->memberships()->where('user_id', $user->getKey())->exists();
    }

    protected static function newFactory(): TeamFactory
    {
        return TeamFactory::new();
    }
}
