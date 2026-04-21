<?php

namespace App\Controller\Frontend;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/shop')]
final class ShopController extends AbstractController
{
    #[Route('', name: 'app_shop')]
    public function index(ProductRepository $productRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $searchTerm = $request->query->get('q');
        $maxPrice = $request->query->getInt('maxPrice', 5000);
        $selectedColors = $request->query->all('colors');

        $department = $request->query->get('department');
        $categorySlug = $request->query->get('category');

        $queryBuilder = $productRepository->createQueryBuilder('p')
            ->leftJoin('p.subCategory', 'sc')
            ->leftJoin('sc.category', 'c')
            ->where('p.price <= :maxPrice')
            ->setParameter('maxPrice', $maxPrice);

        // Track breadcrumb name dynamically
        $currentCategory = 'All Collection';

        // 1. Filter by Master Category (Department)
        if ($department) {
            $categoryMap = [
                'women' => 'Women',
                'men' => 'Men',
                'unisex' => 'Unisex',
                'accessories' => 'Accessories'
            ];

            $mappedDepartment = $categoryMap[strtolower($department)] ?? ucfirst($department);
            $currentCategory = $mappedDepartment;

            $queryBuilder->andWhere('c.name = :department')
                         ->setParameter('department', $mappedDepartment);
        }

        // 2. Filter by Specific Sub-Category
        if ($categorySlug) {
            // Replace hyphens with spaces and capitalize for a clean breadcrumb (e.g., 'maxi-dresses' -> 'Maxi Dresses')
            $currentCategory = ucwords(str_replace('-', ' ', $categorySlug));

            $queryBuilder->andWhere('sc.slug = :categorySlug')
                         ->setParameter('categorySlug', $categorySlug);
        }

        // 3. Filter by Search Query
        if ($searchTerm) {
            $currentCategory = 'Search Results';
            $queryBuilder->andWhere('(p.name LIKE :searchTerm OR p.description LIKE :searchTerm)')
                         ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        // 4. Filter by Color
        if (!empty($selectedColors)) {
            $queryBuilder->andWhere('p.color IN (:colors)')
                         ->setParameter('colors', $selectedColors);
        }

        $queryBuilder->orderBy('p.createdAt', 'DESC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            9
        );

        return $this->render('frontend/shop/index.html.twig', [
            'currentMaxPrice' => $maxPrice,
            'selectedColors'  => $selectedColors,
            'pagination'      => $pagination,
            'searchTerm'      => $searchTerm,
            'currentCategory' => $currentCategory,
        ]);
    }

    // FIX: Renamed route to 'app_product_show' to match your Homepage Twig links
    #[Route('/product/{slug}', name: 'app_product_details')]
    public function show(string $slug, ProductRepository $productRepository): Response
    {
        $product = $productRepository->findOneBy(['slug' => $slug]);

        if (!$product) {
            throw $this->createNotFoundException('This sustainable piece could not be found.');
        }

        return $this->render('frontend/shop/product_show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/journey/{slug}', name: 'app_product_journey', methods: ['GET'])]
    public function journey(Product $product): Response
    {
        return $this->render('frontend/shop/transparency_journey.html.twig', [
            'product' => $product,
        ]);
    }
}
