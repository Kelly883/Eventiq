<?php

namespace App\GraphQL\Types;

use App\Features\Checkout\Models\Order;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class OrderType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Order',
        'description' => 'Organizer order',
        'model' => Order::class,
    ];

    public function fields(): array
    {
        return [
            'id' => ['type' => Type::nonNull(Type::id())],
            'event_id' => ['type' => Type::id()],
            'status' => ['type' => Type::string()],
            'total_amount' => ['type' => Type::string()],
            'currency' => ['type' => Type::string()],
            'payment_gateway' => ['type' => Type::string()],
            'payment_reference' => ['type' => Type::string()],
        ];
    }
}
