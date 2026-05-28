<?php

namespace App\Controller\Authentication;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Registers POST /api/login so Symfony routing reaches the json_login firewall.
 * Authentication is handled by security.yaml (JwtAuthenticationSuccessHandler).
 */
class ApiLoginController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): never
    {
        throw new \LogicException('This endpoint is handled by the json_login firewall.');
    }
}
