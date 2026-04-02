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
    public function __construct(private readonly AdminRepository $adminRepository)
    {
    }

    #[Route(name: 'app_admin_index', methods: ['GET'])]
    public function index(): Response
    {
        $admins = $this->adminRepository->findAll();

        if (empty($admins)) {
            $this->addFlash('warning', 'No Admin found. Please create one first.');
            return $this->redirectToRoute('app_admin_new', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/admin/index.html.twig', [
            'admins' => $admins,
        ]);
    }

    #[Route('/new', name: 'app_admin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, UserPasswordHasherInterface $passwordHasher): Response
    {
        $admin = new Admin();
        $form = $this->createForm(AdminType::class, $admin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user = new User();
            $user->setEmail($form->get('email')->getData());
            $user->setRoles($form->get('roles')->getData());
            $user->setStatus($form->get('status')->getData());
            $user->setIsVerified($form->get('isVerified')->getData());
            $user->setAdmin($admin);

            $plainPassword = $form->get('password')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

            $imageFile = $form->get('avatar')->getData();

            if ($imageFile) {
                $originalFileName = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFileName = $slugger->slug($originalFileName);
                $newFileName = $safeFileName . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move($this->getParameter('admin_images_directory'), $newFileName);
                    $admin->setAvatar($newFileName);
                } catch (FileException $e) {
                    $this->addFlash('error', 'There was an error uploading the profile picture.');
                    $admin->setAvatar('default-avatar.jpg');
                }
            } else {
                $admin->setAvatar('default-avatar.jpg');
            }

            $em->persist($user);
            $em->persist($admin);
            $em->flush();

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
    public function edit(Request $request, Admin $admin, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(AdminType::class, $admin, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user = $admin->getUser();
            if ($user) {
                $user->setEmail($form->get('email')->getData());
                $user->setRoles($form->get('roles')->getData());
                $user->setStatus($form->get('status')->getData());
                $user->setIsVerified($form->get('isVerified')->getData());
            }

            $imageFile = $form->get('avatar')->getData();

            if ($imageFile) {
                $oldAvatar = $admin->getAvatar();
                $originalFileName = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFileName = $slugger->slug($originalFileName);
                $newFileName = $safeFileName . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move($this->getParameter('admin_images_directory'), $newFileName);

                    if ($oldAvatar && $oldAvatar !== 'default-avatar.jpg') {
                        $oldAvatarPath = $this->getParameter('admin_images_directory') . '/' . $oldAvatar;
                        if (file_exists($oldAvatarPath)) {
                            unlink($oldAvatarPath);
                        }
                    }

                    $admin->setAvatar($newFileName);
                } catch (FileException $e) {
                    $this->addFlash('error', 'There was an error uploading the profile picture.');
                }
            }

            $em->flush();

            $this->addFlash('success', 'Admin updated successfully!');
            return $this->redirectToRoute('app_admin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/admin/edit.html.twig', [
            'admin' => $admin,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_delete', methods: ['POST'])]
    public function delete(Request $request, Admin $admin, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $admin->getId(), $request->getPayload()->getString('_token'))) {

            $avatar = $admin->getAvatar();
            if ($avatar && $avatar !== 'default-avatar.jpg') {
                $avatarPath = $this->getParameter('admin_images_directory') . '/' . $avatar;
                if (file_exists($avatarPath)) {
                    unlink($avatarPath);
                }
            }

            $em->remove($admin);
            $em->flush();
            $this->addFlash('success', 'Admin deleted successfully!');
        }

        return $this->redirectToRoute('app_admin_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/toggle-status', name: 'app_admin_user_toggle_status', methods: ['POST'])]
    public function toggleStatus(User $user, EntityManagerInterface $em, Request $request): Response
    {
        if ($this->isCsrfTokenValid('toggle' . $user->getId(), $request->getPayload()->getString('_token'))) {
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

        if ($user->getAdmin()) return $this->redirectToRoute('app_admin_index');
        if ($user->getStaff()) return $this->redirectToRoute('app_staff_index');

        return $this->redirectToRoute('app_customer_index');
    }

    #[Route('/user/{id}/reset-password', name: 'app_admin_reset_password')]
    public function resetPassword(User $user, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        $tempPassword = 'password123';
        $user->setPassword($passwordHasher->hashPassword($user, $tempPassword));
        $em->flush();

        $this->addFlash('success', 'Password reset to: ' . $tempPassword);

        if ($user->getAdmin()) {
            return $this->redirectToRoute('app_admin_edit', ['id' => $user->getAdmin()->getId()]);
        }
        return $this->redirectToRoute('app_admin_index');
    }
}
