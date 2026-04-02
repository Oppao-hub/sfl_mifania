<?php

namespace App\Controller\Dashboard;

use App\Entity\Customer;
use App\Entity\User;
use App\Entity\Wallet;
use App\Form\CustomerType;
use App\Repository\CustomerRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STAFF')]
#[Route('/dashboard/customer')]
final class CustomerController extends AbstractController
{
    public function __construct(private readonly CustomerRepository $customerRepository)
    {
    }

    #[Route(name: 'app_customer_index', methods: ['GET'])]
    public function index(): Response
    {
        $customers = $this->customerRepository->findAll();

        if (empty($customers)) {
            $this->addFlash('warning', 'No Customer found. Please create one first.');
            return $this->redirectToRoute('app_customer_new', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/customer/index.html.twig', [
            'customers' => $customers,
        ]);
    }

    #[Route('/new', name: 'app_customer_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, UserPasswordHasherInterface $passwordHasher): Response
    {
        $customer = new Customer();
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user = new User();
            $user->setEmail($form->get('email')->getData());
            $user->setRoles(['ROLE_CUSTOMER']);
            $user->setCustomer($customer);

            $plainPassword = $form->get('password')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

            $wallet = new Wallet();
            $wallet->setBalance(0);
            $wallet->setRewardPoints(0);
            $customer->setWallet($wallet);

            $imageFile = $form->get('avatar')->getData();

            if ($imageFile) {
                $originalFileName = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFileName = $slugger->slug($originalFileName);
                $newFileName = $safeFileName . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move($this->getParameter('customer_images_directory'), $newFileName);
                    $customer->setAvatar($newFileName);
                } catch (FileException $e) {
                    $this->addFlash('error', 'There was an error uploading the profile picture.');
                    $customer->setAvatar('default-avatar.jpg');
                }
            } else {
                $customer->setAvatar('default-avatar.jpg');
            }

            $em->persist($user);
            $em->persist($customer);
            $em->persist($wallet);
            $em->flush();

            $this->addFlash('success', 'Customer created successfully!');
            return $this->redirectToRoute('app_customer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/customer/new.html.twig', [
            'customer' => $customer,
            'form' => $form,
        ]);
    }

    #[Route('/profile/search', name: 'app_customer_search', methods: ['GET'])]
    public function profileSearch(Request $request): Response
    {
        $query = $request->query->get('q');
        $customer = null;

        if ($query) {
            $customer = $this->customerRepository->findOneBySearch($query);
        }

        return $this->render('dashboard/customer/profile_search.html.twig', [
            'customer' => $customer,
            'query' => $query,
        ]);
    }

    #[Route('/{id}', name: 'app_customer_show', methods: ['GET'])]
    public function show(Customer $customer): Response
    {
        return $this->render('dashboard/customer/show.html.twig', [
            'customer' => $customer,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_customer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Customer $customer, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(CustomerType::class, $customer, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user = $customer->getUser();
            if ($user) {
                $user->setEmail($form->get('email')->getData());
                $user->setStatus($form->get('status')->getData());
                $user->setIsVerified($form->get('isVerified')->getData());
            }

            $imageFile = $form->get('avatar')->getData();

            if ($imageFile) {
                $oldAvatar = $customer->getAvatar();
                $originalFileName = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFileName = $slugger->slug($originalFileName);
                $newFileName = $safeFileName . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move($this->getParameter('customer_images_directory'), $newFileName);

                    if ($oldAvatar && $oldAvatar !== 'default-avatar.jpg') {
                        $oldAvatarPath = $this->getParameter('customer_images_directory') . '/' . $oldAvatar;
                        if (file_exists($oldAvatarPath)) {
                            unlink($oldAvatarPath);
                        }
                    }

                    $customer->setAvatar($newFileName);
                } catch (FileException $e) {
                    $this->addFlash('error', 'There was an error uploading the profile picture.');
                }
            }

            $em->persist($customer);
            $em->flush();

            $this->addFlash('success', 'Customer updated successfully!');
            return $this->redirectToRoute('app_customer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/customer/edit.html.twig', [
            'customer' => $customer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_customer_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Customer $customer, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $customer->getId(), $request->getPayload()->getString('_token'))) {

            $avatar = $customer->getAvatar();
            if ($avatar && $avatar !== 'default-avatar.jpg') {
                $avatarPath = $this->getParameter('customer_images_directory') . '/' . $avatar;
                if (file_exists($avatarPath)) {
                    unlink($avatarPath);
                }
            }

            $em->remove($customer);
            $em->flush();
            $this->addFlash('success', 'Customer deleted successfully!');
        }

        return $this->redirectToRoute('app_customer_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/history', name: 'app_customer_history', methods: ['GET'])]
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
    #[IsGranted('ROLE_ADMIN')]
    public function resetPassword(User $user, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        $tempPassword = 'password123';
        $user->setPassword($passwordHasher->hashPassword($user, $tempPassword));
        $em->flush();

        $this->addFlash('success', 'Password reset to: ' . $tempPassword);

        return $this->redirectToRoute('app_customer_edit', ['id' => $user->getCustomer()->getId()]);
    }
}
