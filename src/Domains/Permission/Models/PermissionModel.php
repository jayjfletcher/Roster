<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Permission\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use JayI\Roster\Database\Factories\PermissionFactory;
use JayI\Roster\Domains\Role\Models\RoleModel;

/**
 * A named ability, e.g. `roster.users.update` or a host's `invoices.edit`.
 *
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property bool $system
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class PermissionModel extends Model
{
    /** @use HasFactory<PermissionFactory> */
    use HasFactory;

    use HasUlids;

    protected $table = 'roster_permissions';

    protected $fillable = ['name', 'description', 'system'];

    protected $attributes = [
        'system' => false,
    ];

    /**
     * @return BelongsToMany<RoleModel, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(RoleModel::class, 'roster_permission_role', 'permission_id', 'role_id');
    }

    protected static function newFactory(): PermissionFactory
    {
        return PermissionFactory::new();
    }

    protected function casts(): array
    {
        return ['system' => 'boolean'];
    }
}
