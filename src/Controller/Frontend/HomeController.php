<?php

namespace App\Controller\Frontend;

use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, ProductRepository $productRepo): Response
    {
        // 1. Get the current gender from Session (Default to 'Women' if not set)
        $currentGender = $request->getSession()->get('shop_gender', 'Women');

        // 2. Fetch the Products (Filtered by Gender)
        $products = $productRepo->findByGender($currentGender);
        // Fetch the 3 newest products
        $topProducts = $productRepo->findTopSellers(3);
        // 3. Send to your Template`
        return $this->render('frontend/home/index.html.twig', [
            'women_count' => $productRepo->count(['gender' => 'Women']),
            'men_count'   => $productRepo->count(['gender' => 'Men']),
            'acc_count' => $productRepo->countByCategoryName('Accessories'),
            'active_gender' => $currentGender,
            'products' => $products,
            'topProducts' => $topProducts,
        ]);
    }

    #[Route('/newsletter/subscribe', name: 'app_newsletter_subscribe', methods: ['POST'])]
    public function subscribe(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        // 1. Validate CSRF Token
        $token = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('newsletter_submit', $token)) {
            $this->addFlash('error', 'Invalid security token. Please try again.');
            return $this->redirectToRoute('app_home');
        }

        // 2. Grab the email from the form
        $email = $request->request->get('email');

        // 3. Validate the email format
        $emailConstraint = new Assert\Email();
        $errors = $validator->validate($email, $emailConstraint);

        if (count($errors) > 0 || empty($email)) {
            $this->addFlash('error', 'Please enter a valid email address.');
            return $this->redirectToRoute('app_home');
        }

        // 4. TODO: Save to Database OR Send to Brevo API
        // Example:
        // $subscriber = new NewsletterSubscriber();
        // $subscriber->setEmail($email);
        // $em->persist($subscriber);
        // $em->flush();

        // 5. Send Success Message and Redirect
        $this->addFlash('success', 'Welcome to the circle! You have successfully subscribed.');

        // Redirect back to the page they came from (or homepage)
        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_home'));
    }
}
