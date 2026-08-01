<?php

namespace App\GraphQL\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

trait QueryOptimizer
{
    /**
     * @param  callable():mixed  $resolver
     */
    protected function rememberGraphQLResult(string $cacheKey, callable $resolver, int $ttlSeconds = 120): mixed
    {
        return Cache::remember($cacheKey, now()->addSeconds($ttlSeconds), $resolver);
    }

    /**
     * @param  array<int,string>  $defaultWith
     */
    protected function optimizeQuery(Builder $query, array $defaultWith = []): Builder
    {
        if (! empty($defaultWith)) {
            $query->with($defaultWith);
        }

        return $query;
    }
}
