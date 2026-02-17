<?php

namespace App\Controller\Customer;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProductRepository; // Assuming you link the QR ID to a product

class EcoController extends AbstractController
{
    #[Route('/scan/{qrId}', name: 'app_eco_scan')]
    public function scan(string $qrId, ProductRepository $productRepository): Response
    {
        // 1. Fetch the product using the QR ID (this ID must be stored on the Product entity)
        $product = $productRepository->findOneBy(['qrCodeId' => $qrId]);

        if (!$product) {
            throw $this->createNotFoundException('Eco information not found for this code.');
        }

        // 2. Pass all required data to a new, focused template
        return $this->render('eco/scan_info.html.twig', [
            'product' => $product,
            // You may need to fetch specialized sustainability data related to the product here
        ]);
    }
}
