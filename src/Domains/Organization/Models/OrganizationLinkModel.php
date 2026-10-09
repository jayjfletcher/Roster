<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Roster\Database\Factories\OrganizationLinkFactory;

/**
 * An organization's record in an external system: which system (`source`),
 * its id there, and its account number.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $source
 * @property string $external_id
 * @property string|null $account_number
 * @property Carbon|null $synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class OrganizationLinkModel extends Model
{
    /** @use HasFactory<OrganizationLinkFactory> */
    use HasFactory;

    use HasUlids;

    protected $table = 'roster_organization_links';

    protected $fillable = ['organization_id', 'source', 'external_id', 'account_number', 'synced_at'];

    /**
     * @return BelongsTo<OrganizationModel, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    protected static function newFactory(): OrganizationLinkFactory
    {
        return OrganizationLinkFactory::new();
    }

    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
        ];
    }
}
