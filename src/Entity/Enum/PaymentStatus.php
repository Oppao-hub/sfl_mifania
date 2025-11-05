<?php

namespace App\Entity\Enum;

enum PaymentStatus: string
{
    case Pending = 'Pending';

    case Paid = 'Paid';

    case Refunded = 'Refunded';
}
