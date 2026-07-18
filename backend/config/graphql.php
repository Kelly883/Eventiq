<?php

use App\GraphQL\Queries\EventsQuery;
use App\GraphQL\Queries\OrdersQuery;
use App\GraphQL\Queries\TicketsQuery;
use App\GraphQL\Types\EventType;
use App\GraphQL\Types\OrderType;
use App\GraphQL\Types\TicketType;

return [
    'route' => [
        'prefix' => 'graphql',
        'controller' => Rebing\GraphQL\GraphQLController::class.'@query',
        'middleware' => ['api.key'],
        'group_attributes' => [],
    ],

    'default_schema' => 'default',

    'schemas' => [
        'default' => [
            'query' => [
                'events' => EventsQuery::class,
                'orders' => OrdersQuery::class,
                'tickets' => TicketsQuery::class,
            ],
            'mutation' => [],
            'middleware' => ['api.key'],
            'method' => ['GET', 'POST'],
        ],
    ],

    'types' => [
        'Event' => EventType::class,
        'Order' => OrderType::class,
        'Ticket' => TicketType::class,
    ],

    'error_formatter' => [Rebing\GraphQL\GraphQL::class, 'formatError'],
    'errors_handler' => [Rebing\GraphQL\GraphQL::class, 'handleErrors'],
    'params_key' => 'variables',
    'security' => [
        'query_max_complexity' => null,
        'query_max_depth' => null,
        'disable_introspection' => false,
    ],
];
