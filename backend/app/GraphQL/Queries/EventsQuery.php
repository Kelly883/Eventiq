<?php

namespace App\GraphQL\Queries;

use App\GraphQL\Concerns\AuthorizesApiScopes;
use App\GraphQL\Concerns\ComplexityAnalyzer;
use App\GraphQL\Concerns\QueryOptimizer;
use App\Models\Event;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Http\Request;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class EventsQuery extends Query
{
    use AuthorizesApiScopes, ComplexityAnalyzer, QueryOptimizer;

    protected $attributes = ['name' => 'events'];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Event'));
    }

    public function authorize($root, array $args, $context, ?ResolveInfo $resolveInfo = null, ?Closure $getSelectFields = null): bool
    {
        $request = $context instanceof Request ? $context : request();
        $this->authorizeScope($request, 'events:read');
        return true;
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $request = $context instanceof Request ? $context : request();
        $this->assertQueryWithinLimits($resolveInfo);

        $cacheKey = 'graphql:events:' . $request->attributes->get('organizer')->id . ':' . md5(json_encode($args));

        return $this->rememberGraphQLResult($cacheKey, function () use ($request) {
            return $this->optimizeQuery(
                Event::query()->where('organizer_id', $request->attributes->get('organizer')->id),
                ['organizer:id']
            )->latest()->get()->all();
        }, (int) config('graphql.cache.ttl', 120));
    }
}
