<?php

namespace App\Controller\Customer;

use App\Entity\SubCategory;
use App\Repository\ProductRepository;
use App\Repository\SubCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;

#[Route('/subcategory')]
final class SubCategoryController extends AbstractController
{
    #[Route(name: 'app_sub_category_index', methods: ['GET'])]
    public function index(SubCategoryRepository $subCategoryRepository): Response
    {
        return $this->render('sub_category/index.html.twig', [
            'sub_categories' => $subCategoryRepository->findAll(),
        ]);
    }

    #[Route('/{slug}', name: 'app_sub_category_show', methods: ['GET'])]
    public function show(SubCategory $subCategory, SubCategoryRepository $subCategoryRepository, ProductRepository $productRepository, Request $request): Response
    {
        $category = $subCategory->getCategory();
        $subCategories = $subCategoryRepository->findBy(['category' => $category], ['name' => 'ASC']);

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 24; // Your items per page

        $query = $productRepository->createQueryBuilder('p')
            ->join('p.subCategory', 's')
            ->where('s.slug = :slug')
            ->setParameter('slug', $subCategory->getSlug())
            ->orderBy('p.name', 'ASC');

        $paginator = new Paginator($query);

        // 4. Set the limits
        $paginator->getQuery()
            ->setFirstResult(($page - 1) * $limit) // Offset
            ->setMaxResults($limit);               // Limit

        $numberOfItems = count($paginator); // This triggers the efficient COUNT query

        // 6. Get the paginated results (the actual products)
        $products = $paginator->getIterator();

        return $this->render('customer/category/show_sub_category.html.twig', [
            'subCategories' => $subCategories,
            'currentSubCategory' => $subCategory,
            'category' => $category,
            'products' => $products, // The iterable list of products
            'paginator' => $paginator, // Pass the paginator object for metadata
            'numberOfItems' => $numberOfItems,
            'page' => $page,
            'limit' => $limit,
        ]);
    }
}
