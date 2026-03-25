<?php

namespace App\Controller\Dashboard\Admin;

use App\Entity\Staff;
use App\Entity\User;
use App\Form\StaffType;
use App\Repository\StaffRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('dashboard/staff')]
final class StaffController extends AbstractController
{
    #[Route(name: 'app_staff_index', methods: ['GET'])]
    public function index(StaffRepository $staffRepository): Response
    {
        $staffs = $staffRepository->findAll();

        if (empty($staffs)) {
            $this->addFlash('warning', 'No Staffs found. Please create one first.');
            return $this->redirectToRoute('app_staff_new', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/staff/index.html.twig', [
            'staffs' => $staffs,
        ]);
    }

    #[Route('/new', name: 'app_staff_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, SluggerInterface $slugger): Response
    {
        $staff = new Staff();
        $form = $this->createForm(StaffType::class, $staff);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = new User();

            // 1. Sync unmapped account data
            $user->setEmail($form->get('email')->getData());
            $user->setStatus($form->get('status')->getData());
            $user->setIsVerified($form->get('isVerified')->getData());
            $user->setRoles(['ROLE_STAFF']);
            $user->setStaff($staff);

            // 2. Hash Password
            $hashedPassword = $passwordHasher->hashPassword($user, $form->get('password')->getData());
            $user->setPassword($hashedPassword);

            // 3. Handle Avatar Upload
            $imageFile = $form->get('avatar')->getData();
            if ($imageFile) {
                $newFileName = $this->handleFileUpload($imageFile, $slugger);
                $staff->setAvatar($newFileName);
            } else {
                $staff->setAvatar('default-avatar.jpg'); // Fallback default image
            }

            $entityManager->persist($staff);
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Staff record created successfully!');
            return $this->redirectToRoute('app_staff_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/staff/new.html.twig', [
            'staff' => $staff,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_staff_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Staff $staff, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(StaffType::class, $staff, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $staff->getUser();

            // 1. Manually update unmapped User fields
            if ($user) {
                $user->setEmail($form->get('email')->getData());
                $user->setStatus($form->get('status')->getData());
                $user->setIsVerified($form->get('isVerified')->getData());
            }

            // 2. Handle Avatar Update & Cleanup
            $imageFile = $form->get('avatar')->getData();
            if ($imageFile) {
                // BUG FIX: Save the old avatar filename before replacing it
                $oldAvatar = $staff->getAvatar();

                $newFileName = $this->handleFileUpload($imageFile, $slugger);
                $staff->setAvatar($newFileName);

                // BUG FIX: Physically delete the old image file (protecting the default image)
                if ($oldAvatar && $oldAvatar !== 'default-avatar.jpg') {
                    $oldAvatarPath = $this->getParameter('staff_images_directory') . '/' . $oldAvatar;
                    if (file_exists($oldAvatarPath)) {
                        unlink($oldAvatarPath);
                    }
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Staff record updated successfully!');
            return $this->redirectToRoute('app_staff_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/staff/edit.html.twig', [
            'staff' => $staff,
            'form' => $form,
        ]);
    }

    /**
     * Helper to process staff avatars
     */
    private function handleFileUpload($imageFile, SluggerInterface $slugger): string
    {
        $originalFileName = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFileName = $slugger->slug($originalFileName);
        $newFileName = $safeFileName . '-' . uniqid() . '.' . $imageFile->guessExtension();

        try {
            $imageFile->move(
                $this->getParameter('staff_images_directory'), // Ensure this exists in services.yaml
                $newFileName
            );
        } catch (FileException $e) {
            $this->addFlash('error', 'There was an error uploading the staff profile picture.');
        }

        return $newFileName;
    }

    #[Route('/{id}', name: 'app_staff_show', methods: ['GET'])]
    public function show(Staff $staff): Response
    {
        return $this->render('dashboard/staff/show.html.twig', [
            'staff' => $staff,
        ]);
    }

    #[Route('/{id}', name: 'app_staff_delete', methods: ['POST'])]
    public function delete(Request $request, Staff $staff, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$staff->getId(), $request->request->get('_token'))) {
            $avatar = $staff->getAvatar();
            if ($avatar && $avatar !== 'default-avatar.jpg') {
                $avatarPath = $this->getParameter('staff_images_directory') . '/' . $avatar;
                if (file_exists($avatarPath)) {
                    unlink($avatarPath);
                }
            }

            $entityManager->remove($staff);
            $entityManager->flush();
            $this->addFlash('success', 'Staff record deleted successfully!');
        }

        return $this->redirectToRoute('app_staff_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/user/{id}/reset-password', name: 'app_staff_reset_password')]
    public function resetPassword(User $user, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $tempPassword = 'password123';
        $user->setPassword($passwordHasher->hashPassword($user, $tempPassword));
        $entityManager->flush();

        $this->addFlash('success', 'Password reset to: ' . $tempPassword);

        return $this->redirectToRoute('app_staff_edit', ['id' => $user->getStaff()->getId()]);
    }
}
