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
    public function index(ProductRepository $productRepository, Request $request, PaginatorInterface $paginator)
    {
        // Use query->get to stay consistent
        $searchTerm = $request->query->get('q');
        $maxPrice = $request->query->getInt('maxPrice', 5000);
        $selectedColors = $request->query->all('colors');

        $queryBuilder = $productRepository->createQueryBuilder('p')
            ->where('p.price <= :maxPrice')
            ->setParameter('maxPrice', $maxPrice);

        if ($searchTerm) {
            $queryBuilder->andWhere('p.name LIKE :searchTerm OR p.description LIKE :searchTerm')
                         ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        // Check if colors exist and filter them
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
            'selectedColors' => $selectedColors,
            'pagination' => $pagination,
            'searchTerm' => $searchTerm,
        ]);
    }

    #[Route('/collection/{collectionName}', name: 'app_shop_collection')]
    public function collection(
        string $collectionName,
        ProductRepository $productRepository,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        $searchTerm = $request->query->get('q');
        $maxPrice = $request->query->getInt('maxPrice', 5000);
        $selectedColors = $request->query->all('colors');

        $queryBuilder = $productRepository->createQueryBuilder('p')
            ->leftJoin('p.category', 'c') // Join the category table
            ->where('p.price <= :maxPrice')
            ->setParameter('maxPrice', $maxPrice);

        if (\in_array(strtolower($collectionName), ['men', 'women'])) {
            $queryBuilder->andWhere('p.gender = :gender')
                         // Assuming your enum values are capitalized like 'Men' or 'Women'
                         ->setParameter('gender', ucfirst($collectionName));
        }else {
            $queryBuilder->andWhere('c.name = :categoryName')
                         ->setParameter('categoryName', ucfirst($collectionName));
        }

        if ($searchTerm) {
            $queryBuilder->andWhere('(p.name LIKE :searchTerm OR p.description LIKE :searchTerm)')
                         ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

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
            'initial_products' => $this->container->get('serializer')->serialize($pagination->getItems(), 'json', ['groups' => 'product:read']),
            'currentMaxPrice' => $maxPrice,
            'selectedColors' => $selectedColors,
            'pagination' => $pagination,
            'currentCategory' => $collectionName,
            'searchTerm' => $searchTerm,
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
