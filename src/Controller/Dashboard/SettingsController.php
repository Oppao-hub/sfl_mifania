<?php

namespace App\Controller\Dashboard;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STAFF')]
#[Route('/dashboard')]
final class SettingsController extends AbstractController
{
    #[Route('/settings', name: 'app_settings')]
    public function index(): Response
    {
        return $this->render('dashboard/settings/index.html.twig', [
            'controller_name' => 'SettingsController',
        ]);
    }
}
