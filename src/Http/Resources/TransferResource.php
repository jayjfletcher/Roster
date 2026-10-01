<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;
use JayI\Roster\Models\Transfer;
use JayI\Roster\Transfers\Transfers;

/**
 * The report's rows are only included for a single transfer.
 *
 * @mixin Transfer
 */
final class TransferResource extends JsonResource
{
    private bool $rows = false;

    private bool $signed = false;

    public function withRows(): self
    {
        $this->rows = true;

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
            'rows' => $this->when($this->rows, fn (): array => $this->rows()),
            'download_url' => match (true) {
                $this->signed => $transfers->signedDownloadUrl($this->resource),
                $transfers->downloadable($this->resource) && Route::has('roster.transfers.download') => route('roster.transfers.download', $this->id),
                default => null,
            },
            'expires_at' => $this->expires_at?->toIso8601String(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
