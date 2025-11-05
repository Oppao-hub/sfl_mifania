<?php

namespace App\Entity\Enum;

enum PaymentMethod: string
{
    case Cash = 'Cash';
    case Credit = 'Credit Card';
    case BankTransfer = 'Bank Transfer';
}
