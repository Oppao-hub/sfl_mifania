<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('ALink')]
final class ALink
{
    public string $class;
    public string $path;
    public string $label;
    public bool $isActive = false;
}


