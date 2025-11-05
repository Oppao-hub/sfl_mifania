<?php
// src/Twig/AppExtension.php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            // The first argument is the name used in Twig (format_id)
            // The second is the method on this class that handles the logic
            new TwigFilter('format_id', [$this, 'formatId']),
        ];
    }

    /**
     * Formats an integer (like a product ID) by padding it with leading zeros.
     * Example: 123456 becomes 00123456 (8 characters total).
     */
    public function formatId(int $id, int $length = 8): string
    {
        // The '%0' . $length . 'd' format ensures a minimum of $length digits,
        // padding with zeros if necessary.
        return sprintf('%0' . $length . 'd', $id);
    }
}
