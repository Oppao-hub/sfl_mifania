<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class SidebarDropdown
{
    public string $d1;
    public string $d2;
    public string $d3;
    public string $cx;
    public string $cy;
    public string $r;
    public string $label;
    public string $icon;
    public bool $isActive = false;
}
