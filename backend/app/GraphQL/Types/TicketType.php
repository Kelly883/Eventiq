<?php

namespace App\GraphQL\Types;

use App\Features\Checkout\Models\Ticket;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class TicketType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Ticket',
        'description' => 'Organizer ticket',
        'model' => Ticket::class,
    ];

    public function fields(): array
    {
        return [
            'id' => ['type' => Type::nonNull(Type::id())],
            'order_id' => ['type' => Type::id()],
            'event_id' => ['type' => Type::id()],
            'ticket_tier_id' => ['type' => Type::id()],
            'status' => ['type' => Type::string()],
            'checked_in' => ['type' => Type::boolean()],
            'checked_in_at' => ['type' => Type::string()],
        ];
    }
}
