<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Generates slugs that are unique within a query's scope.
 */
final class Slugs
{
    /**
     * @param  Builder<covariant Model>  $scope
     */
    public static function unique(string $name, Builder $scope): string
    {
        $base = Str::slug($name);
        $base = $base === '' ? Str::lower(Str::random(8)) : $base;
        $slug = $base;

        for ($suffix = 2; (clone $scope)->where('slug', $slug)->exists(); $suffix++) {
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }
}
