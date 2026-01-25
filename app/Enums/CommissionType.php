<?php

namespace App\Enums;

enum CommissionType: string
{
    case FIXED = 'fixed';
    case PERCENT = 'percent';
    case HYBRID = 'hybrid';
}
