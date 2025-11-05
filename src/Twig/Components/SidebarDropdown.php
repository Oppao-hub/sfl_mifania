<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class SidebarDropdown
{
    public string $icon;
    public array $items = [];
    public string $label;
    public bool $isActive = false;
}
