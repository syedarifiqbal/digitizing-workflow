<?php

namespace App\Enums;

enum OrderPriority: string
{
    case NORMAL = 'normal';
    case RUSH = 'rush';

    public function label(): string
    {
        return match($this) {
            self::NORMAL => 'Normal',
            self::RUSH => 'Rush',
        };
    }
}
