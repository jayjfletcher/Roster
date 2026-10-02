<?php

declare(strict_types=1);

namespace JayI\Roster\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use JayI\Roster\Database\Factories\TransferFactory;
use JayI\Roster\Enums\TransferStatus;
use JayI\Roster\Enums\TransferType;
use JayI\Roster\Support\Users;

/**
 * A CSV import or export: its files, its Impex run, and its report.
 *
 * @property string $id
 * @property TransferType $type
 * @property string|null $organization_id
 * @property int|string $requested_by
 * @property TransferStatus $status
 * @property string|null $impex_run_id
 * @property string|null $input_path
 * @property string|null $output_path
 * @property array<string, mixed>|null $filters
 * @property array<string, mixed>|null $report
 * @property int $row_count
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Transfer extends Model
{
    /** @use HasFactory<TransferFactory> */
    use HasFactory;

    use HasUlids;

    protected $table = 'roster_transfers';

    protected $fillable = [
        'type', 'organization_id', 'requested_by', 'status', 'impex_run_id', 'input_path', 'output_path',
        'filters', 'report', 'row_count', 'confirmed_at', 'finished_at', 'expires_at',
    ];

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
    public function requester(): BelongsTo
    {
        return $this->belongsTo(app(Users::class)->model(), 'requested_by');
    }

    /**
     * @return array<string, int>
     */
    public function summary(): array
    {
        /** @var array<string, int> */
        return (array) ($this->report['summary'] ?? []);
    }

    /**
     * @return HasMany<TransferRow, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(TransferRow::class, 'transfer_id')->orderBy('line');
    }

    /**
     * Every row's plan and result. For large imports, page through `lines()`.
     *
     * @return array<int, array<string, mixed>>
     */
    public function rows(): array
    {
        return $this->lines()->get()->map(fn (TransferRow $row): array => $row->toReport())->all();
    }

    /**
     * Every column but the report, which can be large.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withoutReport(Builder $query): void
    {
        $query->select(array_values(array_diff(['id', ...$this->getFillable(), 'created_at', 'updated_at'], ['report'])));
    }

    protected static function newFactory(): TransferFactory
    {
        return TransferFactory::new();
    }

    protected function casts(): array
    {
        return [
            'type' => TransferType::class,
            'status' => TransferStatus::class,
            'filters' => 'array',
            'report' => 'array',
            'confirmed_at' => 'datetime',
            'finished_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
