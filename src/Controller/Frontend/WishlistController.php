<?php

namespace App\Controller\Frontend;

use App\Entity\Product;
use App\Entity\User;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/wishlist')]
class WishlistController extends AbstractController
{
    #[Route('', name: 'app_wishlist')]
    public function index(Request $request, ProductRepository $productRepository, #[CurrentUser] User $user): Response
    {
        $wishlistProducts = [];

        // 1. Logged-in User: Get products from the database
        if ($user && $user->getCustomer()) {
            $wishlistProducts = $user->getCustomer()->getWishlist();
        }
        // 2. Guest User: Get products from the session
        else {
            $session = $request->getSession();
            $wishlistIds = $session->get('guest_wishlist', []);

            if (!empty($wishlistIds)) {
                $wishlistProducts = $productRepository->findBy(['id' => $wishlistIds]);
            }
        }

        return $this->render('frontend/wishlist/index.html.twig', [
            'products' => $wishlistProducts,
        ]);
    }

    #[Route('/toggle/{id}', name: 'app_wishlist_toggle', methods: ['POST'])]
    public function toggle(Product $product, Request $request, EntityManagerInterface $em, #[CurrentUser] User $user): Response
    {
        // 1. Check if it's a logged-in user
        if ($user && $user->getCustomer()) {
            $customer = $user->getCustomer();

            if ($customer->getWishlist()->contains($product)) {
                $customer->removeWishlist($product);
                $this->addFlash('success', 'Removed from your wishlist.');
            } else {
                $customer->addWishlist($product);
                $this->addFlash('success', 'Added to your wishlist.');
            }
            $em->flush();

        }
        // 2. Session Logic for Guest Users
        else {
            $session = $request->getSession();
            $wishlist = $session->get('guest_wishlist', []);

            if (in_array($product->getId(), $wishlist)) {
                // Remove it
                $wishlist = array_diff($wishlist, [$product->getId()]);
                $this->addFlash('success', 'Removed from your guest wishlist.');
            } else {
                // Add it
                $wishlist[] = $product->getId();
                $this->addFlash('success', 'Added to your guest wishlist.');
            }

            // Save the updated array back to the session
            $session->set('guest_wishlist', $wishlist);
        }

        // Redirect back to wherever they clicked the button
        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_shop'));
    }
}
