<?php

namespace App\Controller\Dashboard\Admin;

use App\Entity\Enum\AccountStatus;
use App\Entity\User;
use App\Entity\Admin;
use App\Form\AdminType;
use App\Repository\AdminRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/dashboard/admin')]
final class AdminController extends AbstractController
{
    #[Route(name: 'app_admin_index', methods: ['GET'])]
    public function index(AdminRepository $adminRepository): Response
    {
        return $this->render('dashboard/admin/index.html.twig', [
            'admins' => $adminRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, SluggerInterface $slugger): Response
    {
        $admin = new Admin();
        $form = $this->createForm(AdminType::class, $admin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user = new User();
            $email = $form->get('email')->getData();
            $plainPassword = $form->get('password')->getData();

            $user->setEmail($email);
            $user->setRoles(['ROLE_ADMIN']);
            $user->setAdmin($admin);
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

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
                        $this->getParameter('admin_images_directory'),
                        $newFileName
                    );
                } catch (FileException $e) {

                }
                $admin->setAvatar($newFileName);
            } else {
                $admin->setAvatar('No Avatar Yet');
            }

            $entityManager->persist($user);
            $entityManager->persist($admin);
            $entityManager->flush();

            $this->addFlash('success', 'Admin created successfully!');
            return $this->redirectToRoute('app_admin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/admin/new.html.twig', [
            'admin' => $admin,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_show', methods: ['GET'])]
    public function show(Admin $admin): Response
    {
        return $this->render('dashboard/admin/show.html.twig', [
            'admin' => $admin,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Admin $admin, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AdminType::class, $admin, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Admin updated successfully!');
            return $this->redirectToRoute('app_admin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/admin/edit.html.twig', [
            'admin' => $admin,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_delete', methods: ['POST'])]
    public function delete(Request $request, Admin $admin, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$admin->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('success', 'Admin deleted successfully!');
            $entityManager->remove($admin);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/toggle-status', name: 'app_admin_user_toggle_status', methods: ['POST'])]
    public function toggleStatus(User $user, EntityManagerInterface $em, Request $request): Response
    {
        // CSRF Security Check
        if ($this->isCsrfTokenValid('toggle' . $user->getId(), $request->request->get('_token'))) {

            // 1. Check current status and switch to the opposite Enum case
            if ($user->getStatus() === AccountStatus::Active) {
                $user->setStatus(AccountStatus::Deactivated);
                $message = 'Deactivated';
            } else {
                $user->setStatus(AccountStatus::Active);
                $message = 'Activated';
            }

            $em->flush();

            $this->addFlash('success', "User has been $message successfully.");
        }

        if($user->getAdmin()) {
            return $this->redirectToRoute('app_admin_index');
        } else if($user->getStaff()){
            return $this->redirectToRoute('app_staff_index');
        }else{
            return $this->redirectToRoute('app_dashboard_customer_index');
        }
    }

    #[Route('/user/{id}/reset-password', name: 'app_admin_reset_password')]
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
        if ($user->getAdmin()) {
            return $this->redirectToRoute('app_admin_edit', ['id' => $user->getAdmin()->getId()]);
        }

        return $this->redirectToRoute('app_admin_index');
    }
}
