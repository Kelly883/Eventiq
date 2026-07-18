<?php

namespace App\GraphQL\Queries;

use App\Features\Checkout\Models\Ticket;
use App\GraphQL\Concerns\AuthorizesApiScopes;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Http\Request;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class TicketsQuery extends Query
{
    use AuthorizesApiScopes;

    protected $attributes = ['name' => 'tickets'];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Ticket'));
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $request = $context instanceof Request ? $context : request();
        $this->authorizeScope($request, 'tickets:read');

        return Ticket::query()
            ->whereHas('event', fn ($query) => $query->where('organizer_id', $request->attributes->get('organizer')->id))
            ->latest()
            ->get();
    }
}
