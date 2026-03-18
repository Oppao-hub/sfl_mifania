<?php

namespace App\Controller\Frontend;

use App\Repository\CustomerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AboutController extends AbstractController
{
    #[Route('/about', name: 'app_about')]
    public function index(CustomerRepository $customerRepo): Response
    {

        $customerCount = \count($customerRepo->findAll());

        return $this->render('frontend/about/index.html.twig', [
            'customerCount' => $customerCount,
        ]);
    }
}
