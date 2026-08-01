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
    'errors_handler' => function (array $errors, $formatter) {
        foreach ($errors as $error) {
            $previous = $error->getPrevious();
            if ($previous instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $previous;
            }
        }
        return Rebing\GraphQL\GraphQL::handleErrors($errors, $formatter);
    },
    'params_key' => 'variables',
    'security' => [
        'query_max_complexity' => (int) env('GRAPHQL_QUERY_MAX_COMPLEXITY', 120),
        'query_max_depth' => (int) env('GRAPHQL_QUERY_MAX_DEPTH', 8),
        'disable_introspection' => false,
    ],
    'cache' => [
        'ttl' => (int) env('GRAPHQL_QUERY_CACHE_TTL', 120),
    ],
];
