<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/device-token')]
class DeviceTokenController extends AbstractController
{
    #[Route('', name: 'api_device_token_register', methods: ['POST'])]
    public function register(Request $request, EntityManagerInterface $em, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        $deviceToken = is_array($payload) ? ($payload['deviceToken'] ?? null) : null;

        if (!is_string($deviceToken) || trim($deviceToken) === '') {
            return $this->json(['error' => 'deviceToken is required'], 400);
        }

        $user->setDeviceToken(trim($deviceToken));
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('', name: 'api_device_token_clear', methods: ['DELETE'])]
    public function clear(EntityManagerInterface $em, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $user->setDeviceToken(null);
        $em->flush();

        return $this->json(['success' => true]);
    }
}
