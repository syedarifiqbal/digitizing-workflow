<?php

namespace App\Enums;

enum OrderPriority: string
{
    case NORMAL = 'normal';
    case RUSH = 'rush';
}
