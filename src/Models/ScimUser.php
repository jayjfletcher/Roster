<?php

declare(strict_types=1);

namespace JayI\Roster\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use JayI\Roster\Support\Users;

/**
 * A user as an organization's identity provider sees them over SCIM. Its id
 * is the SCIM resource id; it outlives the membership so deprovisioned users
 * can be restored.
 *
 * @property string $id
 * @property string $organization_id
 * @property int|string $user_id
 * @property string|null $external_id
 * @property bool $created_by_scim
 * @property bool $deactivated_by_scim
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ScimUser extends Model
{
    use HasUlids;

    protected $table = 'roster_scim_users';

    protected $fillable = ['organization_id', 'user_id', 'external_id', 'created_by_scim', 'deactivated_by_scim', 'active'];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(app(Users::class)->model(), 'user_id');
    }

    protected function casts(): array
    {
        return [
            'created_by_scim' => 'boolean',
            'deactivated_by_scim' => 'boolean',
            'active' => 'boolean',
        ];
    }
}
