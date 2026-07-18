<?php

namespace App\GraphQL\Queries;

use App\GraphQL\Concerns\AuthorizesApiScopes;
use App\Models\Event;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Http\Request;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class EventsQuery extends Query
{
    use AuthorizesApiScopes;

    protected $attributes = ['name' => 'events'];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Event'));
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $request = $context instanceof Request ? $context : request();
        $this->authorizeScope($request, 'events:read');

        return Event::query()
            ->where('organizer_id', $request->attributes->get('organizer')->id)
            ->latest()
            ->get();
    }
}
