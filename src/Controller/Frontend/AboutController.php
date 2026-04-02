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
        $yearsExperience = 5;
        $productCount = $productRepo->count([]);
        
        // Fix: Use LIKE for roles stored as JSON array
        $customerCount = $userRepo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%"ROLE_CUSTOMER"%')
            ->getQuery()
            ->getSingleScalarResult();

        // Dynamic Team Data
        $team = [
            [
                'name' => 'Paolo Mifania',
                'role' => 'Founder & CEO',
                'img' => 'uploads/about/ceo.png',
                'bio' => 'Visionary behind the sustainable movement at Mifania.'
            ],
            [
                'name' => 'Vienna Paola Salazar',
                'role' => 'Fashion Designer',
                'img' => 'uploads/about/designer.png',
                'bio' => 'Crafting elegance with every recycled fiber.'
            ],
            [
                'name' => 'Oppao Imnida',
                'role' => 'Lead Artisan',
                'img' => 'uploads/about/lead-artisan.png',
                'bio' => 'Mastering the craft of eco-friendly craftsmanship.'
            ],
            [
                'name' => 'Paowikan Islander',
                'role' => 'Operations Manager',
                'img' => 'uploads/about/op-manager.png',
                'bio' => 'Ensuring our carbon footprint stays as light as our linen.'
            ]
        ];

        return $this->render('frontend/about/index.html.twig', [
            'yearsExperience' => $yearsExperience,
            'productCount' => $productCount,
            'customerCount' => (int) $customerCount,
            'team' => $team
        ]);
    }
}
