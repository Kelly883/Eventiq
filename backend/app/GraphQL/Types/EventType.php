<?php

namespace App\GraphQL\Types;

use App\Models\Event;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class EventType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Event',
        'description' => 'Organizer event',
        'model' => Event::class,
    ];

    public function fields(): array
    {
        return [
            'id' => ['type' => Type::nonNull(Type::id())],
            'title' => ['type' => Type::nonNull(Type::string())],
            'description' => ['type' => Type::string()],
            'start_date' => ['type' => Type::string()],
            'end_date' => ['type' => Type::string()],
            'location' => ['type' => Type::string()],
            'status' => ['type' => Type::string()],
        ];
    }
}
