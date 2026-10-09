<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * SCIM filter comparisons, case-insensitive as SCIM strings are by default.
 */
final class ScimQuery
{
    /**
     * `eq` is exact: LIKE narrows the candidates, then each is compared in
     * full, so `_` or `%` in a value can never match a different account.
     * `co` and `sw` are searches; wildcards in them only widen the results.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    public static function compare(Builder $builder, string $column, string $operator, string $value): Builder
    {
        if ($operator === 'co') {
            return $builder->whereLike($column, '%'.$value.'%');
        }

        if ($operator === 'sw') {
            return $builder->whereLike($column, $value.'%');
        }

        // Without LIKE wildcards in the value, a case-insensitive LIKE is an
        // exact match: filter in place, inside the caller's scope.
        if (strpbrk($value, '%_') === false) {
            return $builder->whereLike($column, $value);
        }

        // `%` or `_` would widen the LIKE (`a_b@x` matching `axb@x`), so take
        // the LIKE's matches and keep only the exact ones.
        $model = $builder->getModel();

        $keys = $model->newQuery()
            ->whereLike($column, $value)
            ->get()
            ->filter(fn (Model $candidate): bool => strcasecmp((string) $candidate->getAttribute($column), $value) === 0)
            ->modelKeys();

        return $builder->whereIn($model->getQualifiedKeyName(), $keys);
    }
}
