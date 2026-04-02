<?php

namespace App\Controller\Frontend;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class NavbarController extends AbstractController
{
    public function renderNav(CategoryRepository $categoryRepository): Response
    {
        // Fetch all Master Categories (Womenswear, Menswear, Unisex, etc.)
        // We order by ID or Name to keep the menu consistent
        $categories = $categoryRepository->findBy([], ['id' => 'ASC']);

        return $this->render('components/frontend/_navbar.html.twig', [
            'categories' => $categories,
        ]);
    }
}
