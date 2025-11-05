<?php

namespace App\Entity\Enum;

enum OrderStatus: string
{
    case Completed = 'Completed';
    case Pending = 'Pending';
    case Processing = 'Processing';
    case Cancelled = 'Cancelled';
}
