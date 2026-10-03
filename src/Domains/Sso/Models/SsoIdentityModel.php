<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use JayI\Roster\Support\Users;

/**
 * A user's account at an identity provider, matched by its subject.
 *
 * @property string $id
 * @property string $connection_id
 * @property int|string $user_id
 * @property string $subject
 * @property string|null $email
 * @property Carbon|null $last_login_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class SsoIdentityModel extends Model
{
    use HasUlids;

    protected $table = 'roster_sso_identities';

    protected $fillable = ['connection_id', 'user_id', 'subject', 'email', 'last_login_at'];

    /**
     * @return BelongsTo<SsoConnectionModel, $this>
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(SsoConnectionModel::class, 'connection_id');
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
        return ['last_login_at' => 'datetime'];
    }
}
