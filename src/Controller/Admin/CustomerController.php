<?php

namespace App\Controller\Admin;

use App\Entity\Customer;
use App\Form\CustomerType;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/customer')]
final class CustomerController extends AbstractController
{

    public function __construct(private readonly CustomerRepository $customerRepository)
    {
    }

    #[Route(name: 'app_admin_customer_index', methods: ['GET'])]
    public function index(CustomerRepository $customerRepository): Response
    {
        return $this->render('admin/customer/index.html.twig', [
            'customers' => $customerRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_customer_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $customer = new Customer();
        //associate the customer to the user
        $user = $this->getUser();
        //associate the user to the customer
        $customer->setUser($user);

        $form = $this->createForm(CustomerType::class, $customer, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('avatar')->getData();

            if ($imageFile) {
                $originalFileName = pathinfo(
                    $imageFile->getClientOriginalName(),
                    PATHINFO_FILENAME
                );
                $safeFileName = $slugger->slug($originalFileName);
                $newFileName = $safeFileName . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('customer_images_directory'),
                        $newFileName
                    );
                } catch (FileException $e) {

                }
                $customer->setAvatar($newFileName);
            } else {
                $customer->setAvatar('No Avatar Yet');
            }

            $em->persist($customer);
            $em->flush();

            return $this->redirectToRoute('app_admin_customer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/customer/new.html.twig', [
            'customer' => $customer,
            'form' => $form,
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
    }

    #[Route('/profile/search', name: 'app_admin_customer_search', methods: ['GET'])]
    public function profileSearch(Request $request): Response
    {
        // 1. Handle search form submission (e.g., if a customer ID or email is posted)
        $query = $request->query->get('q');
        $customer = null;

        if ($query) {
            // 2. Fetch the customer entity based on the search query
            $customer = $this->customerRepository->findOneBySearch($query);
        }

        // 3. Render the search/profile template
        return $this->render('admin/customer/profile_search.html.twig', [
            'customer' => $customer,
            'query' => $query,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_customer_show', methods: ['GET'])]
    public function show(Customer $customer): Response
    {
        return $this->render('admin/customer/show.html.twig', [
            'customer' => $customer,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_customer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Customer $customer, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(CustomerType::class, $customer, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('avatar')->getData();

            if ($imageFile) {
                $originalFileName = pathinfo(
                    $imageFile->getClientOriginalName(),
                    PATHINFO_FILENAME
                );
                $safeFileName = $slugger->slug($originalFileName);
                $newFileName = $safeFileName . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('customer_images_directory'),
                        $newFileName
                    );
                } catch (FileException $e) {

                }
                $customer->setAvatar($newFileName);
            }

            $entityManager->persist($customer);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_customer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/customer/edit.html.twig', [
            'customer' => $customer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_customer_delete', methods: ['POST'])]
    public function delete(Request $request, Customer $customer, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $customer->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($customer);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_customer_index', [], Response::HTTP_SEE_OTHER);
    }
}
