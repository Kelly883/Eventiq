<?php

namespace App\GraphQL\Queries;

use App\Features\Checkout\Models\Order;
use App\GraphQL\Concerns\AuthorizesApiScopes;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Http\Request;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class OrdersQuery extends Query
{
    use AuthorizesApiScopes;

    protected $attributes = ['name' => 'orders'];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Order'));
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $request = $context instanceof Request ? $context : request();
        $this->authorizeScope($request, 'orders:read');

        return Order::query()
            ->whereHas('event', fn ($query) => $query->where('organizer_id', $request->attributes->get('organizer')->id))
            ->latest()
            ->get();
    }
}
