<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\QRTag;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Encoding\Encoding;
use Symfony\Component\HttpFoundation\JsonResponse;
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

#[Route('/dashboard/products')]
final class ProductController extends AbstractController
{
    #[Route(name: 'app_dashboard_product_index')]
    public function index(ProductRepository $repo): Response
    {
        return $this->render('dashboard/product/index.html.twig', [
            'products' => $repo->findAll(),
            'total_products' => $repo->count([]),
        ]);
    }

    #[Route('/new', name: 'app_dashboard_product_new', methods: ['GET', 'POST'])]
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
                    // ... handle exception if something happens during file upload
                }
                $product->setImage($newFilename);
            }
            $em->persist($product);
            $em->flush();

            // Generate QR Code Value (example: URL or product code)
            $qrValue = 'https://127.0.0.1:8000/products/' . $product->getId();

            //Generate QR Code Image
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

            //save image to file
            $fileName = 'product' . $product->getId() . '.png';
            $result->saveToFile($filePath . '/' . $fileName);

            // Create QRTag entity and relate it to Product
            $qrTag = new QRTag();
            $qrTag->setProduct($product);
            $qrTag->setQrCodeValue($qrValue);
            $qrTag->setQrImagePath($fileName);

            // Save QRTag
            $em->persist($qrTag);
            $em->flush();

            $this->addFlash('success', 'Product created successfully!');
            return $this->redirectToRoute('app_dashboard_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_dashboard_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('dashboard/product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_dashboard_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        //keep current image
        $currentImage = $product->getImage();

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
                    // ... handle exception if something happens during file upload
                }
                $product->setImage($newFilename);

            } else {
                $product->setImage($currentImage);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Product updated successfully!');
            return $this->redirectToRoute('app_dashboard_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_dashboard_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
        }

        $this->addFlash('success', 'Product deleted successfully!');
        return $this->redirectToRoute('app_dashboard_product_index', [], Response::HTTP_SEE_OTHER);
    }
}
