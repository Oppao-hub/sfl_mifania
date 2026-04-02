<?php

namespace App\Controller\Dashboard;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STAFF')]
#[Route('/dashboard/product')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findBy([], ['createdAt' => 'DESC']);

        if(empty($products)) {
            $this->addFlash('warning', 'No products found. Please create one first.');
            return $this->redirectToRoute('app_product_new', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/product/index.html.twig', [
            'products' => $products,
            'total_products' => $productRepository->count([]),
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            // Handle Image Upload
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('product_images_directory'), // Make sure this is in services.yaml
                        $newFilename
                    );
                    $product->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'There was an issue uploading your product image.');
                }
            }

            $entityManager->persist($product);
            $entityManager->flush(); // <--- ActivityLogger subscriber automatically catches this!

            $this->addFlash('success', 'Manifest saved: ' . $product->getName() . ' has been added to the catalog.');
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('dashboard/product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            // Handle Image Upload Replacement
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('product_images_directory'),
                        $newFilename
                    );

                    // Optional: You could add logic here to delete the old image file from the server

                    $product->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'There was an issue uploading the new visual.');
                }
            }

            $entityManager->flush(); // <--- ActivityLogger subscriber catches the exact modified fields!

            $this->addFlash('success', 'Manifest updated: ' . $product->getName() . ' modifications saved.');
            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {

            $productName = $product->getName();

            $entityManager->remove($product);
            $entityManager->flush(); // <--- ActivityLogger subscriber catches the deletion!

            $this->addFlash('success', 'Manifest voided: ' . $productName . ' has been retired from the catalog.');
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }
}
