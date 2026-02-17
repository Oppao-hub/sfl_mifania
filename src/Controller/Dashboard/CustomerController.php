<?php

namespace App\Controller\Dashboard;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\Staff;
use App\Entity\User;
use App\Entity\Wallet;
use App\Form\CustomerType;
use App\Form\StaffType;
use App\Repository\CustomerRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;


#[Route('/customer')]
final class CustomerController extends AbstractController
{

    public function __construct(private readonly CustomerRepository $customerRepository)
    {
    }

    #[Route(name: 'app_dashboard_customer_index', methods: ['GET'])]
    public function index(CustomerRepository $customerRepository): Response
    {
        return $this->render('dashboard/customer/index.html.twig', [
            'customers' => $customerRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_dashboard_customer_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, UserPasswordHasherInterface $passwordHasher): Response
    {
        $customer = new Customer();
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user = new User();
            $email = $form->get('email')->getData();
            $plainPassword = $form->get('password')->getData();

            $user->setEmail($email);
            $user->setRoles(['ROLE_CUSTOMER']);
            $user->setCustomer($customer);
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            $wallet = new Wallet();
            $wallet->setBalance(0);
            $wallet->setRewardPoints(0);
            $customer->setWallet($wallet);

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

            $em->persist($user);
            $em->persist($customer);
            $em->persist($wallet);
            $em->flush();

            $this->addFlash('success', 'Customer created successfully!');
            return $this->redirectToRoute('app_dashboard_customer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/customer/new.html.twig', [
            'customer' => $customer,
            'form' => $form,
        ]);
    }

    #[Route('/profile/search', name: 'app_dashboard_customer_search', methods: ['GET'])]
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
        return $this->render('dashboard/customer/profile_search.html.twig', [
            'customer' => $customer,
            'query' => $query,
        ]);
    }

    #[Route('/{id}', name: 'app_dashboard_customer_show', methods: ['GET'])]
    public function show(Customer $customer): Response
    {
        return $this->render('dashboard/customer/show.html.twig', [
            'customer' => $customer,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_dashboard_customer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Customer $customer, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(CustomerType::class, $customer, ['is_edit' => true]);
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

            $this->addFlash('success', 'Customer updated successfully!');
            return $this->redirectToRoute('app_dashboard_customer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/customer/edit.html.twig', [
            'customer' => $customer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_dashboard_customer_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Customer $customer, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $customer->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($customer);
            $entityManager->flush();
        }

        $this->addFlash('success', 'Customer deleted successfully!');
        return $this->redirectToRoute('app_dashboard_customer_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/history', name: 'app_dashboard_customer_history', methods: ['GET'])]
    public function history(Customer $customer, OrderRepository $orderRepository): Response
    {
        $orders = $orderRepository->findBy(
            ['customer' => $customer],
            ['orderDate' => 'DESC']
        );

        return $this->render('dashboard/customer/history.html.twig', [
            'customer' => $customer,
            'orders' => $orders,
        ]);
    }

    #[Route('/user/{id}/reset-password', name: 'app_customer_reset_password')]
    public function resetPassword(
        User $user,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response
    {
        // 1. Create a generic temporary password
        // You can change this to anything you want
        $tempPassword = 'password123';

        // 2. Hash the password
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $tempPassword
        );

        // 3. Update the user
        $user->setPassword($hashedPassword);
        $entityManager->flush();

        // 4. Show success message
        $this->addFlash('success', 'Password reset to: ' . $tempPassword);

        // 5. Redirect back to the Edit page (so they can see the message)
        return $this->redirectToRoute('app_dashboard_customer_edit', ['id' => $user->getCustomer()->getId()]);
    }
}
