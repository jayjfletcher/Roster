<?php

declare(strict_types=1);

namespace JayI\Roster\Audit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use JayI\Roster\Models\AuditEntry;

/**
 * Writes and checks the hash-chained audit log.
 *
 * Each entry's hash covers its content and the previous entry's hash, so
 * editing any stored entry - or removing one from the middle - breaks every
 * link after it.
 */
final class AuditLog
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function append(array $attributes): AuditEntry
    {
        return DB::transaction(function () use ($attributes): AuditEntry {
            // Lock the chain head, not the newest entry: every writer waits
            // on this one row and then reads the head the last one left.
            $head = $this->chain()->lockForUpdate()->first();

            if ($head === null) {
                $this->chain()->insertOrIgnore(['id' => 1, 'head_hash' => null]);
                $head = $this->chain()->lockForUpdate()->first();
            }

            $previous = $head?->head_hash;

            $attributes['previous_hash'] = is_string($previous) ? $previous : null;
            $attributes['created_at'] = Carbon::now()->startOfSecond();

            $entry = new AuditEntry($attributes);
            $entry->setAttribute('hash', $this->hash($entry));
            $entry->save();

            $this->chain()->update(['head_hash' => $entry->hash]);

            return $entry;
        });
    }

    /**
     * The id of the first entry that no longer matches the chain, or null
     * when the log is intact. After pruning, the oldest remaining entry
     * anchors the chain.
     */
    public function verify(): ?int
    {
        $expected = false;
        $broken = null;

        AuditEntry::query()->orderBy('id')->chunkById(500, function (Collection $entries) use (&$expected, &$broken): bool {
            foreach ($entries as $entry) {
                if ($expected !== false && $entry->previous_hash !== $expected) {
                    $broken = $entry->id;

                    return false;
                }

                if (! hash_equals($entry->hash, $this->hash($entry))) {
                    $broken = $entry->id;

                    return false;
                }

                $expected = $entry->hash;
            }

            return true;
        });

        return $broken;
    }

    /**
     * Delete entries older than `$days` days, returning how many went.
     */
    public function prune(int $days): int
    {
        $cutoff = Carbon::now()->subDays($days);
        $deleted = 0;

        // In batches, so a large prune never holds one long lock.
        do {
            $ids = AuditEntry::query()->toBase()->where('created_at', '<', $cutoff)->orderBy('id')->limit(1000)->pluck('id');
            $deleted += $ids->isEmpty() ? 0 : AuditEntry::query()->toBase()->whereIn('id', $ids)->delete();
        } while ($ids->count() === 1000);

        return $deleted;
    }

    private function chain(): Builder
    {
        return DB::table('roster_audit_chain')->where('id', 1);
    }

    public function hash(AuditEntry $entry): string
    {
        $payload = [
            'source' => $entry->source,
            'action' => $entry->action,
            'actor_id' => $entry->actor_id === null ? null : (string) $entry->actor_id,
            'subject_type' => $entry->subject_type,
            'subject_id' => $entry->subject_id,
            'subject_label' => $entry->subject_label,
            'organization_id' => $entry->organization_id,
            'surface' => $entry->surface,
            'ip' => $entry->ip,
            'user_agent' => $entry->user_agent,
            'changes' => $this->canonical($entry->changes ?? []),
            'context' => $this->canonical($entry->context ?? []),
            'previous_hash' => $entry->previous_hash,
            'created_at' => $entry->created_at->getTimestamp(),
        ];

        return hash('sha256', (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
    }

    /**
     * @param  array<array-key, mixed>  $value
     * @return array<array-key, mixed>
     */
    private function canonical(array $value): array
    {
        if (! array_is_list($value)) {
            ksort($value);
        }

        return array_map(fn (mixed $item): mixed => is_array($item) ? $this->canonical($item) : $item, $value);
    }
}
