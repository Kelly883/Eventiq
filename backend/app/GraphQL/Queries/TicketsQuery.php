<?php

namespace App\GraphQL\Queries;

use App\Features\Checkout\Models\Ticket;
use App\GraphQL\Concerns\AuthorizesApiScopes;
use App\GraphQL\Concerns\ComplexityAnalyzer;
use App\GraphQL\Concerns\QueryOptimizer;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Http\Request;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class TicketsQuery extends Query
{
    use AuthorizesApiScopes, ComplexityAnalyzer, QueryOptimizer;

    protected $attributes = ['name' => 'tickets'];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Ticket'));
    }

    public function authorize($root, array $args, $context, ?ResolveInfo $resolveInfo = null, ?Closure $getSelectFields = null): bool
    {
        $request = $context instanceof Request ? $context : request();
        $this->authorizeScope($request, 'tickets:read');
        return true;
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $request = $context instanceof Request ? $context : request();
        $this->assertQueryWithinLimits($resolveInfo);

        $cacheKey = 'graphql:tickets:' . $request->attributes->get('organizer')->id . ':' . md5(json_encode($args));

        return $this->rememberGraphQLResult($cacheKey, function () use ($request) {
            return $this->optimizeQuery(
                Ticket::query()
                    ->whereHas('event', fn ($query) => $query->where('organizer_id', $request->attributes->get('organizer')->id)),
                ['event:id,organizer_id,title', 'ticketTier:id,event_id,name', 'order:id,event_id,user_id']
            )->latest()->get()->all();
        }, (int) config('graphql.cache.ttl', 120));
    }
}
