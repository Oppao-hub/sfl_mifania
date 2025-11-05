<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('SidebarLink')]
final class SidebarLinkComponent
{
    public string $path;
    public string $label;
    public string $icon;
    public bool $isActive = false;
    public bool $isCollapsed = false;
}


