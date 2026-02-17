<?php

namespace App\Entity\Enum;

enum Size: string
{
    case XS = 'Extra Small';
    case S = 'Small';
    case M = 'Medium';
    case L = 'Large';
    case XL = 'Extra Large';
    case XXL = 'Double Extra Large';
    case XXXL = 'Triple Extra Large';
    case NA = 'N/A';

    public function getLabel(): string
    {
        return match ($this) {
            self::NA => 'N/A',
            self::XS => 'Extra Small',
            self::S => 'Small',
            self::M => 'Medium',
            self::L => 'Large',
            self::XL => 'Extra Large',
            self::XXL => 'Double Extra Large',
            self::XXXL => 'Triple Extra Large',
        };
    }
}

