<?php

declare(strict_types=1);

namespace JayI\Roster\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An email domain whose verified users may auto-join the organization.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $domain
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class OrganizationDomain extends Model
{
    use HasUlids;

    protected $table = 'roster_organization_domains';

    protected $fillable = ['organization_id', 'domain'];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }
}
