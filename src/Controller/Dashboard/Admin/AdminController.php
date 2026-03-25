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
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
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

            $user->setEmail($form->get('email')->getData());

            // --- PRIVILEGE UPDATE: Grab the roles dynamically from the form checkboxes! ---
            $user->setRoles($form->get('roles')->getData());
            // ------------------------------------------------------------------------------

            $user->setStatus($form->get('status')->getData());
            $user->setIsVerified($form->get('isVerified')->getData());
            $user->setAdmin($admin);

            $plainPassword = $form->get('password')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

            $imageFile = $form->get('avatar')->getData();
            if ($imageFile) {
                $newFileName = $this->handleFileUpload($imageFile, $slugger);
                $admin->setAvatar($newFileName);
            } else {
                $admin->setAvatar('sample_avatar.jpeg'); // Default avatar
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
    public function edit(Request $request, Admin $admin, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(AdminType::class, $admin, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $admin->getUser();

            $user->setEmail($form->get('email')->getData());

            // --- PRIVILEGE UPDATE: Allow updating roles from the form checkboxes! ---
            $user->setRoles($form->get('roles')->getData());
            // ------------------------------------------------------------------------

            $user->setStatus($form->get('status')->getData());
            $user->setIsVerified($form->get('isVerified')->getData());

            $imageFile = $form->get('avatar')->getData();
            if ($imageFile) {
                // 2. BUG FIX: Store old filename before overwrite
                $oldAvatar = $admin->getAvatar();

                $newFileName = $this->handleFileUpload($imageFile, $slugger);
                $admin->setAvatar($newFileName);

                // 3. BUG FIX: Delete old image, but protect the default image from being deleted!
                if ($oldAvatar && $oldAvatar !== 'sample_avatar.jpeg' && $oldAvatar !== 'default-avatar.jpg') {
                    $oldAvatarPath = $this->getParameter('admin_images_directory') . '/' . $oldAvatar;
                    if (file_exists($oldAvatarPath)) {
                        unlink($oldAvatarPath);
                    }
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Admin updated successfully!');
            return $this->redirectToRoute('app_admin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/admin/edit.html.twig', [
            'admin' => $admin,
            'form' => $form,
        ]);
    }

    private function handleFileUpload($imageFile, SluggerInterface $slugger): string
    {
        $originalFileName = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFileName = $slugger->slug($originalFileName);
        $newFileName = $safeFileName . '-' . uniqid() . '.' . $imageFile->guessExtension();

        try {
            $imageFile->move(
                $this->getParameter('admin_images_directory'),
                $newFileName
            );
        } catch (FileException $e) {
            $this->addFlash('error', 'Failed to upload image.');
        }

        return $newFileName;
    }

    #[Route('/{id}/delete', name: 'app_admin_delete', methods: ['POST'])]
    public function delete(Request $request, Admin $admin, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$admin->getId(), $request->request->get('_token'))) {

            // 4. BUG FIX: Delete the physical image when the Admin account is destroyed
            $avatar = $admin->getAvatar();
            if ($avatar && $avatar !== 'sample_avatar.jpeg' && $avatar !== 'default-avatar.jpg') {
                $avatarPath = $this->getParameter('admin_images_directory') . '/' . $avatar;
                if (file_exists($avatarPath)) {
                    unlink($avatarPath);
                }
            }

            $entityManager->remove($admin);
            $entityManager->flush();
            $this->addFlash('success', 'Admin deleted successfully!');
        }

        return $this->redirectToRoute('app_admin_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/toggle-status', name: 'app_admin_user_toggle_status', methods: ['POST'])]
    public function toggleStatus(User $user, EntityManagerInterface $em, Request $request): Response
    {
        if ($this->isCsrfTokenValid('toggle' . $user->getId(), $request->request->get('_token'))) {
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

        if($user->getAdmin()) return $this->redirectToRoute('app_admin_index');
        if($user->getStaff()) return $this->redirectToRoute('app_staff_index');

        return $this->redirectToRoute('app_dashboard_customer_index');
    }

    #[Route('/user/{id}/reset-password', name: 'app_admin_reset_password')]
    public function resetPassword(User $user, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $tempPassword = 'password123';
        $user->setPassword($passwordHasher->hashPassword($user, $tempPassword));
        $entityManager->flush();

        $this->addFlash('success', 'Password reset to: ' . $tempPassword);

        if ($user->getAdmin()) {
            return $this->redirectToRoute('app_admin_edit', ['id' => $user->getAdmin()->getId()]);
        }

        return $this->redirectToRoute('app_admin_index');
    }
}
