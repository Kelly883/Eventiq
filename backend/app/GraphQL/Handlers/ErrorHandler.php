<?php

namespace App\GraphQL\Handlers;

use Rebing\GraphQL\GraphQL;

class ErrorHandler
{
    public function __invoke(array $errors, $formatter)
    {
        return self::handle($errors, $formatter);
    }

    public static function handle(array $errors, $formatter)
    {
        foreach ($errors as $error) {
            $previous = $error->getPrevious();
            if ($previous instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $previous;
            }
        }
        return GraphQL::handleErrors($errors, $formatter);
    }
}
