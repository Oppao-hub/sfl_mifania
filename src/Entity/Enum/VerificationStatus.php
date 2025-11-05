<?php

namespace App\Entity\Enum;

enum VerificationStatus: string
{
    case Pending = 'Pending';
    case Verified = 'Verified';
}
