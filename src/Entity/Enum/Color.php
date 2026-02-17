<?php

namespace App\Entity\Enum;

enum Color: string
{
    // These values will be stored in the database/form submission
    case RED = 'Red';
    case BLUE = 'Blue';
    case GREEN = 'Green';
    case BLACK = 'Black';
    case WHITE = 'White';
    case BROWN = 'Brown';
    case MOCHA = 'Mocha';
    case BEIGE = 'Beige';

    /**
     * Returns the hexadecimal color code for the Enum case, used in Twig/CSS.
     */
    public function getHexCode(): string
    {
        return match ($this) {
            self::RED => '#FF0000',
            self::BLUE => '#0000FF',
            self::GREEN => '#008000',
            self::BLACK => '#000000',
            self::WHITE => '#FFFFFF',
            self::BROWN => '#A52A2A',
            self::MOCHA => '#C8B085',
            self::BEIGE => '#F5F5DC',
        };
    }
}
