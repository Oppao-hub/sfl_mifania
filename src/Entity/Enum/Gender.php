<?php

namespace App\Entity\Enum;

enum Gender: string
{
    case MEN = 'Men';
    case WOMEN = 'Women';
    case UNISEX = 'Unisex';
}
