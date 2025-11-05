<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('action_icon')]
final class ActionIcon
{
    public string $path;
    public string $svgPath;
    public ?string $label = null;
    public ?string $class = null;
    public bool $isActive = false;
}
