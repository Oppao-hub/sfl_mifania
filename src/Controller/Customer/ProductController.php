<?php

namespace App\Controller\Customer;

use App\Form\AddToCartType;
use App\DTO\CartItemDTO;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/product')]
final class ProductController extends AbstractController
{
    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        return $this->render('customer/product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    #[Route('/{slug}', name: 'app_product_show')]
    public function show(Request $request, Product $product, CartService $cartService): Response
    {
        $dto = new CartItemDTO();
        $form = $this->createForm(AddToCartType::class, $dto, [
            'product' => $product,
            'max_quantity' => 10,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cartService->addItem(
                $product,
                $dto->quantity,
            );
        }

        return $this->render('customer/product/show.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }
}
