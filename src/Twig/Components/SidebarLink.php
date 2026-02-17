<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('SidebarLink')]
final class SidebarLink
{
    public string $path;
    public string $cx;
    public string $cy;
    public string $r;
    public string $label;
    public string $icon;
    public bool $isActive = false;
    public bool $isCollapsed = false;
}
