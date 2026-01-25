<?php

namespace App\Enums;

enum RoleType: string
{
    case SALES = 'sales';
    case DESIGNER = 'designer';

    public function label(): string
    {
        return match($this) {
            self::SALES => 'Sales Commission',
            self::DESIGNER => 'Designer Bonus',
        };
    }
}
