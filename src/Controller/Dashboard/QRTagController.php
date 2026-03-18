<?php

namespace App\Controller\Dashboard;

use App\Entity\QRTag;
use App\Entity\Product;
use App\Form\QRTagType;
use App\Repository\QRTagRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\QrCode as QrCodeQrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/dashboard/qrTag')]
final class QRTagController extends AbstractController
{
    #[Route('/', name: 'app_qrTag_index', methods: ['GET'])]
    public function index(QRTagRepository $qRTagRepository): Response
    {
        $qrTags = $qRTagRepository->findAll();

        if (empty($qrTags)) {
            $this->addFlash('warning', 'No QR Tags found. Please create one first.');
            return $this->redirectToRoute('app_qrTag_new', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/qrTag/index.html.twig', [
            'qrTags' => $qrTags,
        ]);
    }

    #[Route('/new', name: 'app_qrTag_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $qRTag = new QRTag();
        $form = $this->createForm(QRTagType::class, $qRTag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product = $qRTag->getProduct();

            if (!$product) {
                $this->addFlash('error', 'Please select a product to generate the QR Code.');
                return $this->redirectToRoute('app_qrTag_new');
            }

            // 1. Generate the image and get the filename and value using our new helper method
            $qrData = $this->generateAndSaveQrCode($product);
            $qRTag->setQrCodeValue($qrData['value']);
            $qRTag->setQrImagePath($qrData['filename']);

            try {
                $entityManager->persist($qRTag);
                $entityManager->flush();

                $this->addFlash('success', 'QR Tag generated and saved successfully!');
                return $this->redirectToRoute('app_qrTag_index', [], Response::HTTP_SEE_OTHER);
            } catch (UniqueConstraintViolationException $e) {
                // BUG FIX: Delete the orphaned image if the database rejects the save!
                $this->deleteImageFile($qrData['filename']);
                $this->addFlash('error', 'A QR Tag already exists for this product.');
            } catch (\Exception $e) {
                $this->deleteImageFile($qrData['filename']);
                $this->addFlash('error', 'An error occurred while saving the QR Tag.');
            }
        }

        return $this->render('dashboard/qrTag/new.html.twig', [
            'qrTag' => $qRTag,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_qrTag_show', methods: ['GET'])]
    public function show(QRTag $qRTag): Response
    {
        return $this->render('dashboard/qrTag/show.html.twig', [
            'qrTag' => $qRTag,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_qrTag_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, QRTag $qRTag, EntityManagerInterface $entityManager): Response
    {
        $originalQrImagePath = $qRTag->getQrImagePath();
        $form = $this->createForm(QRTagType::class, $qRTag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product = $qRTag->getProduct();

            if (!$product) {
                $this->addFlash('error', 'Please select a product to generate the QR code.');
                return $this->redirectToRoute('app_qrTag_edit', ['id' => $qRTag->getId()]);
            }

            // 1. Generate the new image using our helper method
            $qrData = $this->generateAndSaveQrCode($product);
            $qRTag->setQrCodeValue($qrData['value']);
            $qRTag->setQrImagePath($qrData['filename']);

            // 2. Delete the old image so they don't pile up
            if ($originalQrImagePath && $originalQrImagePath !== $qrData['filename']) {
                $this->deleteImageFile($originalQrImagePath);
            }

            $entityManager->flush();
            $this->addFlash('success', 'QR Tag updated and QR code regenerated successfully!');

            return $this->redirectToRoute('app_qrTag_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/qrTag/edit.html.twig', [
            'qrTag' => $qRTag,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_qrTag_delete', methods: ['POST'])]
    public function delete(Request $request, QRTag $qRTag, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $qRTag->getId(), $request->getPayload()->getString('_token'))) {

            // BUG FIX: Actually delete the physical image from the server when deleting the DB record
            if ($qRTag->getQrImagePath()) {
                $this->deleteImageFile($qRTag->getQrImagePath());
            }

            $entityManager->remove($qRTag);
            $entityManager->flush();

            $this->addFlash('success', 'QR Tag deleted successfully!');
        }

        return $this->redirectToRoute('app_qrTag_index', [], Response::HTTP_SEE_OTHER);
    }

    // --- PRIVATE HELPER METHODS ---

    /**
     * Generates the QR Code PNG and saves it to the disk.
     * Returns an array with the URL value and the generated filename.
     */
    private function generateAndSaveQrCode(Product $product): array
    {
        $qrValue = $this->generateUrl(
            'app_product_journey',
            ['slug' => $product->getSlug()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $qrCode = new QrCodeQrCode(
            data: $qrValue,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0), // You can change this to 82, 98, 46 for Mifania Brand Green!
            backgroundColor: new Color(255, 255, 255),
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        $filePath = $this->getParameter('product_qr_directory');
        $fileName = 'product-' . $product->getId() . '-' . uniqid() . '.png';

        $result->saveToFile($filePath . '/' . $fileName);

        return [
            'value' => $qrValue,
            'filename' => $fileName
        ];
    }

    /**
     * Safely deletes an image file from the QR directory
     */
    private function deleteImageFile(string $filename): void
    {
        $filePath = $this->getParameter('product_qr_directory') . '/' . $filename;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
