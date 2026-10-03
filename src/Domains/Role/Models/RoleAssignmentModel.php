<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Support\Users;

/**
 * A role held by a user: globally, in an organization, or on a team.
 *
 * @property string $id
 * @property string $role_id
 * @property int|string $user_id
 * @property string|null $organization_id
 * @property string|null $team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class RoleAssignmentModel extends Model
{
    use HasUlids;

    protected $table = 'roster_role_assignments';

    protected $fillable = ['role_id', 'user_id', 'organization_id', 'team_id'];

    /**
     * @return BelongsTo<RoleModel, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(RoleModel::class, 'role_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(app(Users::class)->model(), 'user_id');
    }

    /**
     * @return BelongsTo<OrganizationModel, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    /**
     * @return BelongsTo<TeamModel, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(TeamModel::class, 'team_id');
    }
}
