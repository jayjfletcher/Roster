<?php

declare(strict_types=1);

namespace JayI\Roster\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A bearer token an identity provider uses to provision one organization.
 * Only its hash is stored.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $name
 * @property string $token_hash
 * @property string|null $sso_connection_id
 * @property int|string|null $created_by
 * @property Carbon|null $last_used_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ScimToken extends Model
{
    use HasUlids;

    protected $table = 'roster_scim_tokens';

    protected $fillable = ['organization_id', 'name', 'token_hash', 'sso_connection_id', 'created_by', 'last_used_at', 'expires_at', 'revoked_at'];

    protected $hidden = ['token_hash'];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * @return BelongsTo<SsoConnection, $this>
     */
    public function ssoConnection(): BelongsTo
    {
        return $this->belongsTo(SsoConnection::class, 'sso_connection_id');
    }

    public function isUsable(): bool
    {
        return $this->revoked_at === null && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
