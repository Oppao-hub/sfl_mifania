<?php

namespace App\Entity\Enum;

enum PaymentStatus: string
{
    case PENDING = 'Pending';
    case PAID = 'Paid';
    case REFUNDED = 'Refunded';
    case FAILED = 'Failed';
}
