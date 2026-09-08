<?php

namespace App\Features\ApiKeys\Enums;

enum ApiKeyScope: string
{
    case EventsRead = 'events:read';
    case EventsWrite = 'events:write';
    case OrdersRead = 'orders:read';
    case OrdersWrite = 'orders:write';
    case TicketsRead = 'tickets:read';
    case TicketsWrite = 'tickets:write';

    /**
     * The GraphQL query layer (`events`, `orders`, `tickets`) and the REST
     * v1 surface are read-only today, so only the `*:read` scopes gate a
     * real endpoint. The `*:write` scopes are reserved for the upcoming
     * mutation/CRUD surface and must not be granted until it ships.
     */
    public function isAvailable(): bool
    {
        return ! str_contains($this->value, ':write');
    }

    /**
     * Scopes that currently gate an actual API surface.
     */
    public static function available(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $scope) => $scope->isAvailable()
        ));
    }

    public static function availableValues(): array
    {
        return array_map(
            fn (self $scope) => $scope->value,
            self::available()
        );
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}