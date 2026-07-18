<?php

namespace App\Http;

use App\Http\Middleware\ApiKeyMiddleware;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middlewareGroups = [
        'api' => [
            ApiKeyMiddleware::class,
        ],
    ];
}
