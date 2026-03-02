<?php

namespace App\Entity\Enum;

enum Provider: string
{
    case MANUAL = 'Manual';
    case GOOGLE = 'Google';
}
