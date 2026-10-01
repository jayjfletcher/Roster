<?php

declare(strict_types=1);

namespace JayI\Roster\Transfers\Flows\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Audit\Surface;
use JayI\Roster\Models\Transfer;
use JayI\Roster\Transfers\Planners\Planner;
use JayI\Roster\Transfers\TransferContext;
use JayI\Roster\Transfers\Transfers;

/**
 * Apply one row, as the person who confirmed the import, so every change is
 * attributed to them in the audit log.
 */
final class ApplyImportRow
{
    public function __construct(
        private readonly Auth $auth,
        private readonly TransferContext $context,
        private readonly Surface $surface,
    ) {}

    /**
     * @param  array{transfer: string, line: int, values: array<string, string>}  $item
     * @return array{line: int, action: string, reasons: array<int, string>}
     */
    public function execute(array $item): array
    {
        $transfer = Transfer::query()->findOrFail($item['transfer']);
        $requester = $transfer->requester;
        $actor = $requester instanceof Model ? $requester : null;
        $guard = $this->auth->guard();
        $previous = $guard->user();

        if ($actor instanceof Authenticatable) {
            $guard->setUser($actor);
        }

        $this->context->transfer = $transfer;

        try {
            $outcome = $this->surface->using('import', fn (): array => app(Transfers::class)->planner($transfer)->apply($item['values'], $transfer, $actor));
        } catch (ValidationException $exception) {
            $outcome = ['action' => Planner::ERROR, 'reasons' => [(string) collect($exception->errors())->flatten()->first()]];
        } finally {
            $this->context->transfer = null;

            if ($previous instanceof Authenticatable) {
                $guard->setUser($previous);
            } elseif (method_exists($guard, 'forgetUser')) {
                $guard->forgetUser();
            }
        }

        return ['line' => $item['line']] + $outcome;
    }
}
