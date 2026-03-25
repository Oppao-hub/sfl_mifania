<?php

namespace App\Controller\Frontend;

use App\Repository\UserRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/about')]
class AboutController extends AbstractController
{
     #[Route('', name: 'app_about')]
    public function about(UserRepository $userRepo, ProductRepository $productRepo): Response
    {
        // Dynamic counts for the stats banner
        $customerCount = $userRepo->count(['roles' => 'ROLE_USER']); // adjust role as needed
        $productCount = $productRepo->count([]);

        // Dynamic Team Data (You could also move this to a TeamMember Entity later)
        $team = [
            ['name' => 'Paolo Mifania', 'role' => 'Founder & CEO', 'img' => 'uploads/about/ceo.png'],
            ['name' => 'Vienna Paola Salazar', 'role' => 'Fashion Designer', 'img' => 'uploads/about/designer.png'],
            ['name' => 'Oppao Imnida', 'role' => 'Lead Artisan', 'img' => 'uploads/about/lead-artisan.png'],
            ['name' => 'Paowikan Islander', 'role' => 'Operations Manager', 'img' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb']
        ];

        return $this->render('frontend/about/index.html.twig', [
            'customerCount' => $customerCount,
            'productCount' => $productCount,
            'team' => $team
        ]);
    }
}
