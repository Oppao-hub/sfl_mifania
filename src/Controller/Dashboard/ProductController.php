<?php

namespace App\Controller\Dashboard;

use App\Entity\Product;
use App\Entity\QRTag;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\QrCode as QrCodeQrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[IsGranted('ROLE_STAFF')]
#[Route('/dashboard/product')]
final class ProductController extends AbstractController
{
    #[Route('/', name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();

        if (empty($products)) {
            $this->addFlash('warning', 'No Products found. Please create one first.');
            return $this->redirectToRoute('app_product_new', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/product/index.html.twig', [
            'products' => $products,
            'total_products' => $productRepository->count([]),
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('product_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading image.');
                }
                $product->setImage($newFilename);
            }
            $em->persist($product);
            $em->flush();

            // Generate a dynamic, production-ready absolute URL for the QR Code
            $qrValue = $this->generateUrl(
                'app_product_show',
                ['id' => $product->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            // Generate QR Code Image
            $qrCode = new QrCodeQrCode(
                data: $qrValue,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 300,
                margin: 10,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
                foregroundColor: new Color(0, 0, 0),
                backgroundColor: new Color(255, 255, 255),
            );

            $writer = new PngWriter();
            $result = $writer->write($qrCode);

            // File path to save the QR image
            $filePath = $this->getParameter('product_qr_directory');
            $fileName = 'product' . $product->getId() . '.png';
            $result->saveToFile($filePath . '/' . $fileName);

            // Create QRTag entity and relate it to Product
            $qrTag = new QRTag();
            $qrTag->setProduct($product);
            $qrTag->setQrCodeValue($qrValue);
            $qrTag->setQrImagePath($fileName);

            $em->persist($qrTag);
            $em->flush();

            $this->addFlash('success', 'Product created successfully!');
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

        $currentImage = $product->getImage();

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                // BUG FIX: Store the old image filename before replacing it
                $oldImage = $product->getImage();

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('product_images_directory'),
                        $newFilename
                    );

                    // BUG FIX: Physically delete the old product image from the server
                    if ($oldImage) {
                        $oldImagePath = $this->getParameter('product_images_directory') . '/' . $oldImage;
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }

                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading image.');
                }
                $product->setImage($newFilename);

            } else {
                $product->setImage($currentImage);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Product updated successfully!');
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
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
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->getPayload()->getString('_token'))) {

            // BUG FIX: Actually delete the physical image file before removing the database record!
            if ($product->getImage()) {
                $imagePath = $this->getParameter('product_images_directory') . '/' . $product->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $entityManager->remove($product);
            $entityManager->flush();
        }

        $this->addFlash('success', 'Product deleted successfully!');
        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }
}
