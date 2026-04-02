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

        // Grab the new parameters from the URL (e.g., /shop?department=women  OR  /shop?category=blazers)
        $department = $request->query->get('department');
        $categorySlug = $request->query->get('category');

        // Always join the categories so we can filter by them safely
        $queryBuilder = $productRepository->createQueryBuilder('p')
            ->leftJoin('p.subCategory', 'sc')
            ->leftJoin('sc.category', 'c')
            ->where('p.price <= :maxPrice')
            ->setParameter('maxPrice', $maxPrice);

        // 1. Filter by Master Category (Department)
        if ($department) {
            // Map simple URL strings to your exact Database Master Categories
            $categoryMap = [
                'women' => 'Women',
                'men' => 'Men',
                'unisex' => 'Unisex',
                'accessories' => 'Accessories'
            ];

            $mappedDepartment = $categoryMap[strtolower($department)] ?? ucfirst($department);

            $queryBuilder->andWhere('c.name = :department')
                         ->setParameter('department', $mappedDepartment);
        }

        // 2. Filter by Specific Sub-Category
        if ($categorySlug) {
            $queryBuilder->andWhere('sc.slug = :categorySlug')
                         ->setParameter('categorySlug', $categorySlug);
        }

        // 3. Filter by Search Query
        if ($searchTerm) {
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

        // Determine what to show in the Breadcrumbs/Header
        $currentCategory = 'All';
        if ($department) {
            $currentCategory = $mappedDepartment ?? ucfirst($department);
        } elseif ($categorySlug) {
            $currentCategory = $categorySlug;
        }

        return $this->render('frontend/shop/index.html.twig', [
            'currentMaxPrice' => $maxPrice,
            'selectedColors'  => $selectedColors,
            'pagination'      => $pagination,
            'searchTerm'      => $searchTerm,
            'currentCategory' => $currentCategory, // Passes clean text to your Twig breadcrumbs!
        ]);
    }

    #[Route('/product/{slug}', name: 'app_product_details')]
    public function show(string $slug, ProductRepository $productRepository): Response
    {
        // Fetch specifically by the slug from the URL
        $product = $productRepository->findOneBy(['slug' => $slug]);

        if (!$product) {
            throw $this->createNotFoundException('Product not found.');
        }

        return $this->render('frontend/shop/product_show.html.twig', [
            'product' => $product,
            // We still pass the slug if you want Alpine for secondary interactions
            'slug' => $slug
        ]);
    }

    #[Route('/journey/{slug}', name: 'app_product_journey', methods: ['GET'])]
    public function journey(Product $product): Response
    {
        // You can fetch extra 'Transparency' data from your database here
        return $this->render('frontend/shop/transparency_journey.html.twig', [
            'product' => $product,
        ]);
    }
}
