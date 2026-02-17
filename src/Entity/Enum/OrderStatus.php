<?php

namespace App\Entity\Enum;

enum OrderStatus: string
{
    case COMPLETED = 'Completed';
    case PENDING = 'Pending';
    case PROCESSING = 'Processing';
    case SHIPPED = 'Shipped';
    case DELIVERED = 'Delivered';
    case CANCELLED = 'Cancelled';
}
