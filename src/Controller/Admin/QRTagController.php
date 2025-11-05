<?php

namespace App\Controller\Admin;

use App\Entity\QRTag;
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
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/admin/qrtag')]
#[IsGranted('ROLE_ADMIN')]
final class QRTagController extends AbstractController
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    #[Route('/', name: 'app_admin_qrtag_index', methods: ['GET'])]
    public function index(QRTagRepository $qRTagRepository): Response
    {
        return $this->render('admin/qrtag/index.html.twig', [
            'qrtags' => $qRTagRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_qrtag_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $qRTag = new QRTag();

        $form = $this->createForm(QRTagType::class, $qRTag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $product = $qRTag->getProduct();

            if (!$product) {
                $this->addFlash('error', 'Please select a product to generate the QR Code.');
                return $this->redirectToRoute('app_admin_qrtag_new');
            }

            // Generate QR Code Value (example: URL or product code)
            $qrValue = $this->urlGenerator->generate(
                'app_product_show',
                ['id' => $product->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $qRTag->setQrCodeValue($qrValue);

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
            $fileName = 'product-' . $product->getId() . '-' . uniqid() . '.png';
            $result->saveToFile($filePath . '/' . $fileName);

            $qRTag->setQrImagePath($fileName);

            // Save QRTa
            try {
                $entityManager->persist($qRTag);
                $entityManager->flush();

                $this->addFlash('success', 'QR Tag generated and saved successfully!');
                return $this->redirectToRoute('app_admin_qrtag_index', [], Response::HTTP_SEE_OTHER);
            } catch (UniqueConstraintViolationException $e) {
                // Store the error in flash
                $this->addFlash('error', 'A QR Tag already exists for this entry.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while saving the QR Tag.');
            }
        }

        return $this->render('admin/qrtag/new.html.twig', [
            'qrtag' => $qRTag,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_qrtag_show', methods: ['GET'])]
    public function show(QRTag $qRTag): Response
    {
        return $this->render('admin/qrtag/show.html.twig', [
            'qrtag' => $qRTag,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_qrtag_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, QRTag $qRTag, EntityManagerInterface $entityManager): Response
    {
        // Store the original QR image path to potentially delete it later
        $originalQrImagePath = $qRTag->getQrImagePath();

        $form = $this->createForm(QRTagType::class, $qRTag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product = $qRTag->getProduct(); // Get the product selected in the form

            // IMPORTANT: Ensure a product is selected before proceeding
            if (!$product) {
                $this->addFlash('error', 'Please select a product to generate the QR code.');
                return $this->redirectToRoute('app_admin_qrtag_edit', ['id' => $qRTag->getId()]); // Redirect back to form
            }

            // --- QR Code Regeneration Logic (similar to 'new' action) ---

            // Generate QR Code Value based on the (potentially new) product
            $qrValue = $this->urlGenerator->generate(
                'app_product_show', // Your product detail route name
                ['id' => $product->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $qRTag->setQrCodeValue($qrValue); // Update the QR code value on the entity

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

            $filePath = $this->getParameter('product_qr_directory');
            $newFileName = 'product-' . $product->getId() . '-' . uniqid() . '.png'; // Generate a new unique filename
            $result->saveToFile($filePath . '/' . $newFileName);

            // Set the new image path on the entity
            $qRTag->setQrImagePath($newFileName);

            // OPTIONAL: Delete the old QR image file if it exists and a new one was generated
            // This is crucial to prevent accumulation of old QR images
            if ($originalQrImagePath && file_exists($filePath . '/' . $originalQrImagePath)) {
                // Ensure the new filename is different from the old one before deleting
                // to avoid deleting the image we just created if product ID happened to be same.
                if ($originalQrImagePath !== $newFileName) {
                    unlink($filePath . '/' . $originalQrImagePath);
                    $this->addFlash('info', 'Old QR image deleted.');
                }
            }
            // --- End QR Code Regeneration Logic ---

            // Flush all changes (product, qrCodeValue, qrImagePath, and any other fields)
            $entityManager->flush();
            $this->addFlash('success', 'QR Tag updated and QR code regenerated successfully!');

            return $this->redirectToRoute('app_admin_qrtag_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/qrtag/edit.html.twig', [
            'qrtag' => $qRTag,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_qrtag_delete', methods: ['POST'])]
    public function delete(Request $request, QRTag $qRTag, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $qRTag->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($qRTag);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_qrtag_index', [], Response::HTTP_SEE_OTHER);
    }
}
