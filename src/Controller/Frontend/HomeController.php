<?php

namespace App\Controller\Frontend;

use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\NewsletterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, ProductRepository $productRepo, CategoryRepository $categoryRepo): Response
    {
        $currentCategory = $request->getSession()->get('shop_category', 'Women');

        $products = $productRepo->findByMasterCategory($currentCategory);
        $topProducts = $productRepo->findTopSellers(3);
        $featuredProducts = $productRepo->findBy([], ['createdAt' => 'DESC'], 4);

        $team = [
            [
                'name' => 'Paolo Mifania',
                'role' => 'Founder & CEO',
                'img' => 'ceo.png',
                'bio' => 'Visionary behind the sustainable movement at Mifania.'
            ],
            [
                'name' => 'Vienna Paola Salazar',
                'role' => 'Fashion Designer',
                'img' => 'designer.png',
                'bio' => 'Crafting elegance with every recycled fiber.'
            ],
            [
                'name' => 'Oppao Gomez',
                'role' => 'Lead Artisan',
                'img' => 'lead-artisan.png',
                'bio' => 'Mastering the craft of eco-friendly craftsmanship.'
            ],
            [
                'name' => 'Bien Eltanal',
                'role' => 'Operations Manager',
                'img' => 'op-manager.png',
                'bio' => 'Ensuring our carbon footprint stays as light as our linen.'
            ]
        ];

        $testimonials = [
            [
                'quote'   => "Finding clothes that align with my values usually means compromising on style or fit. Mifania is the first brand where I didn't have to choose. The craftsmanship is flawless.",
                'author'  => 'Elena R.',
                'details' => 'Verified Buyer • Wearing Size S',
                'rating'  => 5,
                'image' => 'Elena.jpeg',
            ],
            [
                'quote'   => "I was worried the organic linen would be scratchy, but it is incredibly soft and drapes perfectly. I've washed it five times and it still looks brand new.",
                'author'  => 'Sarah T.',
                'details' => 'Verified Buyer • Wearing Size M',
                'rating'  => 5,
                'image' => 'Sarah.jpeg',
            ],
            [
                'quote'   => "Finally, a brand that actually backs up its sustainability claims. The carbon-neutral shipping and zero-waste packaging are just the cherry on top of a beautiful coat.",
                'author'  => 'Marcus J.',
                'details' => 'Verified Buyer • Wearing Size L',
                'rating'  => 5,
                'image' => 'Marcus.jpeg',
            ],
        ];

        $faqs = [
            ['q' => 'How do you source your materials?', 'a' => 'We source directly from GOTS certified farms in India and Turkey.'],
            ['q' => 'Is your packaging plastic-free?', 'a' => 'Yes! Every order ships in 100% compostable mailers made from cornstarch, and we use soy-based inks for all our tags.'],
            ['q' => 'What is your return policy?', 'a' => 'We offer a 30-day window for circular exchanges.'],
            ['q' => 'Do you ship international?', 'a' => 'Currently we ship to 15 countries with carbon-neutral carriers.'],
            ['q' => 'How should I care for my organic garments?', 'a' => 'Wash cold, hang dry.'],
            ['q' => 'Are your workers paid fair wages?', 'a' => 'Yes, we are SA8000 certified.'],
            ['q' => 'Do you have a recycling program for old clothes?', 'a' => 'Yes, check our Circle program.']
        ];

        return $this->render('frontend/home/index.html.twig',[
            'women_count'      => $productRepo->countByMasterCategory('Women'),
            'men_count'        => $productRepo->countByMasterCategory('Men'),
            'acc_count'        => $productRepo->countByMasterCategory('Accessories'),
            'unisex_count'     => $productRepo->countByMasterCategory('Unisex'),
            'women_categories' => $categoryRepo->findByName('Women'),
            'men_categories'   => $categoryRepo->findByName('Men'),
            'acc_categories'   => $categoryRepo->findByName('Accessories'),
            'active_category'  => $currentCategory,
            'products'         => $products,
            'topProducts'      => $topProducts,
            'newArrivals'      => $featuredProducts,
            'categories'       => $categoryRepo->findBy([], null, 3),
            'testimonials'     => $testimonials,
            'team'             => $team,
            'faqs'             => $faqs,
        ]);
    }

    #[Route('/newsletter/subscribe', name: 'app_newsletter_subscribe', methods: ['POST'])]
    public function subscribe(Request $request, ValidatorInterface $validator, NewsletterService $newsletterService): Response
    {
        $token = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('newsletter_submit', $token)) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_home');
        }

        $email = $request->request->get('email');
        $errors = $validator->validate($email, new Assert\Email());

        if (\count($errors) > 0 || empty($email)) {
            $this->addFlash('error', 'Please enter a valid email address.');
            return $this->redirectToRoute('app_home');
        }

        $newsletterService->processNewSubscription($email);

        $this->addFlash('success', 'Welcome to the circle! Check your email for your 10% discount code.');
        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_home'));
    }
}
