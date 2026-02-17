<?php

namespace App\Controller\Customer;

use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, ProductRepository $productRepo): Response
    {
        // 1. Get the current gender from Session (Default to 'Women' if not set)
        $currentGender = $request->getSession()->get('shop_gender', 'Women');

        // 2. Fetch the Products (Filtered by Gender)
        $products = $productRepo->findByGender($currentGender);

        // 3. Send to your Template
        return $this->render('home/index.html.twig', [
            'active_gender' => $currentGender,
            'products' => $products,
        ]);
    }

    #[Route('/switch-context/{gender}', name: 'app_switch_context')]
    public function changeContext(string $gender, Request $request): Response
    {
        // 1. Validate inputs (Security)
        if (!in_array($gender, ['Men', 'Women'])) {
            $gender = 'Women'; // Default fallback
        }

        // 2. Save preference to User Session
        $request->getSession()->set('shop_gender', $gender);

        // 3. Reload the page (redirect to homepage)
        return $this->redirectToRoute('app_home');
    }
}
