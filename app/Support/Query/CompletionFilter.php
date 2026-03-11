<?php

namespace App\Support\Query;

use App\Enums\CompletionFilterEnum;
use Illuminate\Database\Eloquent\Builder;

class CompletionFilter
{
    /**
     * @param  callable(Builder):void  $newCallback
     * @param  callable(Builder):void  $completedCallback
     */
    public static function apply(
        Builder $query,
        ?string $completion,
        callable $newCallback,
        callable $completedCallback
    ): void {
        if (! $completion) {
            return;
        }

        if ($completion === CompletionFilterEnum::NEW->value) {
            $newCallback($query);

            return;
        }

        if ($completion === CompletionFilterEnum::COMPLETED->value) {
            $completedCallback($query);
        }
    }
}
