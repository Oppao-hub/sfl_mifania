<?php

namespace App\Controller\Customer;


use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\SubCategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\Tools\Pagination\Paginator;

#[Route('/category')]
class CategoryController extends AbstractController
{
    #[Route(name: 'app_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('customer/category/index.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/{slug}', name: 'app_category_show', methods: ['GET'])]
    public function show(Category $category, SubCategoryRepository $subCategoryRepository, ProductRepository $productRepository, Request $request): Response
    {
        $subCategories = $subCategoryRepository->findBy(['category' => $category], ['name' => 'ASC']);

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 24; // Your items per page

        $query = $productRepository->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->where('c.slug = :slug')
            ->setParameter('slug', $category->getSlug())
            ->orderBy('p.name', 'ASC');

        $paginator = new Paginator($query);

        $paginator->getQuery()
            ->setFirstResult(($page - 1) * $limit) // Offset
            ->setMaxResults($limit);               // Limit

        $numberOfItems = count($paginator); // This triggers the efficient COUNT query

        // 6. Get the paginated results (the actual products)
        $products = $paginator->getIterator();

        return $this->render('customer/category/show_category.html.twig', [
            'subCategories' => $subCategories,
            'currentSubCategory' => null,
            'category' => $category,
            'products' => $products, // The iterable list of products
            'paginator' => $paginator, // Pass the paginator object for metadata
            'numberOfItems' => $numberOfItems,
            'page' => $page,
            'limit' => $limit,
        ]);
    }
}
