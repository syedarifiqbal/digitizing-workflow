<?php

namespace App\Enums;

enum OrderType: string
{
    case DIGITIZING = 'digitizing';
    case VECTOR = 'vector';
    case PATCH = 'patch';
    case QUOTATION = 'quotation';
}
