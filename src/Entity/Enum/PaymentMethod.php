<?php

namespace App\Entity\Enum;

enum PaymentMethod: string
{
    case CASH = 'Cash';
    case CREDIT = 'Credit Card';
    case BANK_TRANSFER = 'Bank Transfer';
    case PAYPAL = 'Paypal';
}
