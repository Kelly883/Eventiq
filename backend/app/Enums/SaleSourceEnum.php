<?php

namespace App\Enums;

enum SaleSourceEnum: string
{
    case Web = 'web';
    case Mobile = 'mobile';
    case Pos = 'pos';
    case Admin = 'admin';
    case Api = 'api';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
