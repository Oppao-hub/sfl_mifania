<?php

namespace App\Controller\Customer;


use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RequestStack;

final class WishlistController extends AbstractController
{
   #[Route('/wishlist', name: 'app_wishlist')]
    public function index(RequestStack $requestStack, ProductRepository $productRepo): Response
    {
        // 1. Get the list of IDs from the session
        $session = $requestStack->getSession();
        $wishlistIds = $session->get('wishlist', []);

        // 2. Fetch the actual Product entities from the database
        $products = $productRepo->findBy(['id' => $wishlistIds]);

        return $this->render('customer/wishlist/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/wishlist/add/{id}', name: 'app_wishlist_add')]
    public function add(int $id, RequestStack $requestStack, Request $request): Response
    {
        $session = $requestStack->getSession();

        // Get current wishlist (or empty array if none)
        $wishlist = $session->get('wishlist', []);

        // Logic: Toggle (Add if missing, Remove if exists)
        if (!in_array($id, $wishlist)) {
            $wishlist[] = $id;
            $this->addFlash('success', 'Added to wishlist!');
        } else {
            // Remove it
            $key = array_search($id, $wishlist);
            unset($wishlist[$key]);
            $this->addFlash('info', 'Removed from wishlist.');
        }

        // Save back to session
        $session->set('wishlist', $wishlist);

        // Redirect back to the page they were on
        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_home'));
    }

    #[Route('/wishlist/clear', name: 'app_wishlist_clear')]
    public function clear(RequestStack $requestStack): Response
    {
        $requestStack->getSession()->remove('wishlist');
        $this->addFlash('success', 'Wishlist cleared.');

        return $this->redirectToRoute('app_wishlist');
    }
}
