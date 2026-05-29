<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\ProductRepository;
use App\Service\RealtimeSync;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/me/wishlist')]
#[IsGranted('ROLE_CUSTOMER')]
class WishlistController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('', name: 'api_me_wishlist_list', methods: ['GET'])]
    public function list(#[CurrentUser] User $user): JsonResponse
    {
        $customer = $user->getCustomer();
        if (!$customer) {
            return $this->json(['error' => 'Customer profile not found.'], 404);
        }

        $products = $customer->getWishlist()->getValues();
        $member = $this->serializer->normalize($products, 'json', ['groups' => 'product:read']);

        return $this->json([
            '@context' => '/api/contexts/Product',
            '@id' => '/api/me/wishlist',
            '@type' => 'Collection',
            'member' => $member,
            'totalItems' => \count($products),
        ]);
    }

    #[Route('/{id}/toggle', name: 'api_me_wishlist_toggle', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(
        int $id,
        ProductRepository $productRepository,
        EntityManagerInterface $em,
        RealtimeSync $realtimeSync,
        #[CurrentUser] User $user,
    ): JsonResponse {
        $customer = $user->getCustomer();
        if (!$customer) {
            return $this->json(['error' => 'Customer profile not found.'], 404);
        }

        $product = $productRepository->find($id);
        if (!$product) {
            throw new NotFoundHttpException('Product not found.');
        }

        if ($customer->getWishlist()->contains($product)) {
            $customer->removeWishlist($product);
            $added = false;
        } else {
            $customer->addWishlist($product);
            $added = true;
        }

        $em->flush();

        $realtimeSync->publishEntityChange('wishlist', $added ? 'created' : 'deleted', [
            'entityId' => (string) $id,
        ], $user->getId());

        $productData = $this->serializer->normalize($product, 'json', ['groups' => 'product:read']);

        return $this->json([
            'added' => $added,
            'product' => $productData,
        ]);
    }
}
