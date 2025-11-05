<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('icon')]
final class Icon
{
    public string $d = ''; // the SVG path data
    public string $class = ''; // optional Tailwind classes
    public int $strokeWidth = 2; // optional stroke width
}
