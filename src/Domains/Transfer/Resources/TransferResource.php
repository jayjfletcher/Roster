<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer\Resources;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;
use JayI\Roster\Domains\Transfer\Models\TransferModel;
use JayI\Roster\Domains\Transfer\Models\TransferRowModel;
use JayI\Roster\Domains\Transfer\Services\Transfers;
use JayI\Roster\Domains\User\Resources\UserSummaryResource;

/**
 * A single transfer includes its rows, a page at a time (`rows_page`, 100
 * per page).
 *
 * @mixin TransferModel
 */
final class TransferResource extends JsonResource
{
    private bool $rows = false;

    private int $rowsPage = 1;

    /** @var LengthAwarePaginator<int, TransferRowModel>|null */
    private ?LengthAwarePaginator $rowsPaginator = null;

    private bool $signed = false;

    public function withRows(int $page = 1): self
    {
        $this->rows = true;
        $this->rowsPage = max(1, $page);

        return $this;
    }

    /**
     * Use a short-lived signed download link instead of the API route.
     */
    public function signed(): self
    {
        $this->signed = true;

        return $this;
    }

    /**
     * @return LengthAwarePaginator<int, TransferRowModel>
     */
    private function rowsPage(): LengthAwarePaginator
    {
        return $this->rowsPaginator ??= $this->resource->lines()->paginate(100, ['*'], 'rows_page', $this->rowsPage);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $requester = $this->requester;
        $transfers = app(Transfers::class);

        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'organization' => $this->organization?->slug,
            'requested_by' => $requester instanceof Model ? (new UserSummaryResource($requester))->resolve($request) : null,
            'row_count' => $this->row_count,
            'progress' => $transfers->progress($this->resource),
            'summary' => $this->summary(),
            'results' => (array) ($this->report['results'] ?? []),
            'error' => $this->report['error'] ?? null,
            'filters' => $this->filters,
            'rows' => $this->when($this->rows, fn (): array => array_map(fn (TransferRowModel $row): array => $row->toReport(), $this->rowsPage()->items())),
            'rows_meta' => $this->when($this->rows, function (): array {
                $page = $this->rowsPage();

                return ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()];
            }),
            'download_url' => match (true) {
                $this->signed => $transfers->signedDownloadUrl($this->resource),
                // Lists trust the record; only a single transfer checks the file
                // (one disk call per row is slow on S3).
                $transfers->downloadable($this->resource, checkFile: $this->rows) && Route::has('roster.transfers.download') => route('roster.transfers.download', $this->id),
                default => null,
            },
            'expires_at' => $this->expires_at?->toIso8601String(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
