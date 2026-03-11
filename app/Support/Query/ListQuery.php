<?php

namespace App\Support\Query;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ListQuery
{
    public static function searchTerm(Request $request, string $key = 'search'): ?string
    {
        $search = trim((string) $request->input($key, ''));

        return $search === '' ? null : $search;
    }

    public static function sortDirection(Request $request, string $key = 'sort_dir', string $default = 'desc'): string
    {
        $direction = strtolower((string) $request->input($key, $default));

        return $direction === 'asc' ? 'asc' : 'desc';
    }

    /**
     * @param  array<string, string>  $sortMap
     * @param  callable(Builder):void  $defaultSort
     */
    public static function applySort(
        Builder $query,
        Request $request,
        array $sortMap,
        callable $defaultSort,
        string $sortByKey = 'sort_by',
        string $sortDirKey = 'sort_dir'
    ): void {
        $sortBy = (string) $request->input($sortByKey, '');
        $sortDirection = self::sortDirection($request, $sortDirKey);

        if (isset($sortMap[$sortBy])) {
            $query->orderBy($sortMap[$sortBy], $sortDirection);

            return;
        }

        $defaultSort($query);
    }
}
