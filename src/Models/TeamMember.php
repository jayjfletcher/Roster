<?php

declare(strict_types=1);

namespace JayI\Roster\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A seat on a team, held through an organization membership.
 *
 * @property string $id
 * @property string $team_id
 * @property string $membership_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class TeamMember extends Model
{
    use HasUlids;

    protected $table = 'roster_team_members';

    protected $fillable = ['team_id', 'membership_id'];

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    /**
     * @return BelongsTo<Membership, $this>
     */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class, 'membership_id');
    }
}
