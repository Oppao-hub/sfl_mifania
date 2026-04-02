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
    public function __construct(private readonly StaffRepository $staffRepository)
    {
    }

    #[Route(name: 'app_staff_index', methods: ['GET'])]
    public function index(): Response
    {
        $staffs = $this->staffRepository->findAll();

        if (empty($staffs)) {
            $this->addFlash('warning', 'No Staff found. Please create one first.');
            return $this->redirectToRoute('app_staff_new', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/staff/index.html.twig', [
            'staffs' => $staffs,
        ]);
    }

    #[Route('/new', name: 'app_staff_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, UserPasswordHasherInterface $passwordHasher): Response
    {
        $staff = new Staff();
        $form = $this->createForm(StaffType::class, $staff);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user = new User();
            $user->setEmail($form->get('email')->getData());
            $user->setStatus($form->get('status')->getData());
            $user->setIsVerified($form->get('isVerified')->getData());
            $user->setRoles(['ROLE_STAFF']);
            $user->setStaff($staff);

            $plainPassword = $form->get('password')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

            $imageFile = $form->get('avatar')->getData();

            if ($imageFile) {
                $originalFileName = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFileName = $slugger->slug($originalFileName);
                $newFileName = $safeFileName . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move($this->getParameter('staff_images_directory'), $newFileName);
                    $staff->setAvatar($newFileName);
                } catch (FileException $e) {
                    $this->addFlash('error', 'There was an error uploading the profile picture.');
                    $staff->setAvatar('default-avatar.jpg');
                }
            } else {
                $staff->setAvatar('default-avatar.jpg');
            }

            $em->persist($user);
            $em->persist($staff);
            $em->flush();

            $this->addFlash('success', 'Staff created successfully!');
            return $this->redirectToRoute('app_staff_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/staff/new.html.twig', [
            'staff' => $staff,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_staff_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Staff $staff, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(StaffType::class, $staff, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user = $staff->getUser();
            if ($user) {
                $user->setEmail($form->get('email')->getData());
                $user->setStatus($form->get('status')->getData());
                $user->setIsVerified($form->get('isVerified')->getData());
            }

            $imageFile = $form->get('avatar')->getData();

            if ($imageFile) {
                $oldAvatar = $staff->getAvatar();
                $originalFileName = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFileName = $slugger->slug($originalFileName);
                $newFileName = $safeFileName . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move($this->getParameter('staff_images_directory'), $newFileName);

                    if ($oldAvatar && $oldAvatar !== 'default-avatar.jpg') {
                        $oldAvatarPath = $this->getParameter('staff_images_directory') . '/' . $oldAvatar;
                        if (file_exists($oldAvatarPath)) {
                            unlink($oldAvatarPath);
                        }
                    }

                    $staff->setAvatar($newFileName);
                } catch (FileException $e) {
                    $this->addFlash('error', 'There was an error uploading the profile picture.');
                }
            }

            $em->flush();

            $this->addFlash('success', 'Staff updated successfully!');
            return $this->redirectToRoute('app_staff_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/staff/edit.html.twig', [
            'staff' => $staff,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_staff_show', methods: ['GET'])]
    public function show(Staff $staff): Response
    {
        return $this->render('dashboard/staff/show.html.twig', [
            'staff' => $staff,
        ]);
    }

    #[Route('/{id}', name: 'app_staff_delete', methods: ['POST'])]
    public function delete(Request $request, Staff $staff, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $staff->getId(), $request->getPayload()->getString('_token'))) {

            $avatar = $staff->getAvatar();
            if ($avatar && $avatar !== 'default-avatar.jpg') {
                $avatarPath = $this->getParameter('staff_images_directory') . '/' . $avatar;
                if (file_exists($avatarPath)) {
                    unlink($avatarPath);
                }
            }

            $em->remove($staff);
            $em->flush();
            $this->addFlash('success', 'Staff deleted successfully!');
        }

        return $this->redirectToRoute('app_staff_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/user/{id}/reset-password', name: 'app_staff_reset_password')]
    public function resetPassword(User $user, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        $tempPassword = 'password123';
        $user->setPassword($passwordHasher->hashPassword($user, $tempPassword));
        $em->flush();

        $this->addFlash('success', 'Password reset to: ' . $tempPassword);

        return $this->redirectToRoute('app_staff_edit', ['id' => $user->getStaff()->getId()]);
    }
}
