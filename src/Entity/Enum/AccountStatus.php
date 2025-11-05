<?php

namespace App\Entity\Enum;

enum AccountStatus: string
{
    case Active = 'Active';
    case Inactive = 'Inactive';
    case Suspended = 'Suspended';
}
