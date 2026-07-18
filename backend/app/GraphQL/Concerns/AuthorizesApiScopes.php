<?php

namespace App\GraphQL\Concerns;

use GraphQL\Error\Error;
use Illuminate\Http\Request;

trait AuthorizesApiScopes
{
    protected function authorizeScope(Request $request, string $scope): void
    {
        $scopes = $request->attributes->get('api_key_scopes', []);

        if (! in_array($scope, $scopes, true)) {
            throw new Error("The API key is missing the required [{$scope}] scope.");
        }
    }
}
