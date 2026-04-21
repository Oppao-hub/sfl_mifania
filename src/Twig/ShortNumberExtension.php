<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ShortNumberExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('short_num', [$this, 'formatShortNumber']),
        ];
    }

    public function formatShortNumber(int|float $number): string
    {
        if ($number >= 1000000000) {
            return number_format($number / 1000000000, 1) . 'B'; // Billions
        }
        if ($number >= 1000000) {
            return number_format($number / 1000000, 1) . 'M'; // Millions
        }
        if ($number >= 1000) {
            return number_format($number / 1000, 1) . 'K'; // Thousands
        }

        // If it's less than 1000, just return the normal number with commas
        return number_format($number);
    }
}
