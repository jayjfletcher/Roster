<?php

declare(strict_types=1);

namespace JayI\Roster\Transfers;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use JayI\Impex\Impex;
use JayI\Impex\Models\Batch;
use JayI\Roster\Enums\TransferStatus;
use JayI\Roster\Enums\TransferType;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Transfer;
use JayI\Roster\Transfers\Exporters\AuditExporter;
use JayI\Roster\Transfers\Exporters\Exporter;
use JayI\Roster\Transfers\Exporters\MembersExporter;
use JayI\Roster\Transfers\Exporters\UsersExporter;
use JayI\Roster\Transfers\Planners\MembersPlanner;
use JayI\Roster\Transfers\Planners\Planner;
use JayI\Roster\Transfers\Planners\TeamsPlanner;
use JayI\Roster\Transfers\Planners\UsersPlanner;
use LogicException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV import and export, run as Impex flows. Impex is optional.
 */
class Transfers
{
    public const string IMPORT_FLOW = 'roster:import';

    public const string EXPORT_FLOW = 'roster:export';

    public function available(): bool
    {
        return class_exists(Impex::class);
    }

    public function ensureAvailable(): void
    {
        if (! $this->available()) {
            throw TransfersUnavailableException::make();
        }
    }

    public function planner(Transfer $transfer): Planner
    {
        return match ($transfer->type) {
            TransferType::ImportMembers => app(MembersPlanner::class),
            TransferType::ImportUsers => app(UsersPlanner::class),
            TransferType::ImportTeams => app(TeamsPlanner::class),
            default => throw new LogicException("[{$transfer->type->value}] is not an import."),
        };
    }

    public function exporter(Transfer $transfer): Exporter
    {
        return match ($transfer->type) {
            TransferType::ExportMembers => app(MembersExporter::class),
            TransferType::ExportUsers => app(UsersExporter::class),
            TransferType::ExportAudit => app(AuditExporter::class),
            default => throw new LogicException("[{$transfer->type->value}] is not an export."),
        };
    }

    public function disk(): Filesystem
    {
        return Storage::disk((string) config('roster.transfers.disk', 'local'));
    }

    /**
     * How far a running import has got: rows applied (or failed) of the
     * total, from Impex's batch counters. Null before rows are applied.
     *
     * @return array{done: int, total: int}|null
     */
    public function progress(Transfer $transfer): ?array
    {
        if (! $this->available() || $transfer->impex_run_id === null) {
            return null;
        }

        $batch = Batch::query()->where('run_id', $transfer->impex_run_id)->latest()->first();

        return $batch === null ? null : ['done' => $batch->succeeded + $batch->failed, 'total' => $batch->total];
    }

    /**
     * Whether a finished export's file is still there to download.
     */
    public function downloadable(Transfer $transfer): bool
    {
        return ! $transfer->type->isImport()
            && $transfer->status === TransferStatus::Completed
            && $transfer->output_path !== null
            && $this->disk()->exists($transfer->output_path);
    }

    /**
     * Stream an export's CSV. Callers authorize first.
     */
    public function download(Transfer $transfer): StreamedResponse
    {
        abort_unless($this->downloadable($transfer), 404);

        $name = Str::slug($transfer->type->value.'-'.($transfer->organization->slug ?? 'all').'-'.$transfer->created_at?->format('Y-m-d-His')).'.csv';

        return $this->disk()->download((string) $transfer->output_path, $name, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * A short-lived signed link to an export, for MCP clients that can't send
     * the API's credentials. SECURITY: whoever holds it can download until it
     * expires.
     */
    public function signedDownloadUrl(Transfer $transfer): ?string
    {
        if (! $this->downloadable($transfer) || ! Route::has('roster.transfers.file')) {
            return null;
        }

        return URL::temporarySignedRoute('roster.transfers.file', now()->addMinutes(15), ['transfer' => $transfer->id]);
    }

    /**
     * The organization a transfer's permission is checked in: the
     * organization for organization-wide types (and an organization's audit
     * log), otherwise none (global).
     */
    public static function scope(TransferType $type, ?Organization $organization): ?Organization
    {
        return $type->needsOrganization() || $type === TransferType::ExportAudit ? $organization : null;
    }
}
