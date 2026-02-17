<?php

namespace App\Controller\Admin;

use App\Entity\Staff;
use App\Entity\User;
use App\Form\StaffType;
use App\Repository\StaffRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/staff')]
final class StaffController extends AbstractController
{
    #[Route(name: 'app_staff_index', methods: ['GET'])]
    public function index(StaffRepository $staffRepository): Response
    {
        return $this->render('dashboard/staff/index.html.twig', [
            'staffs' => $staffRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_staff_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $staff = new Staff();
        $form = $this->createForm(StaffType::class, $staff);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user = new User();
            $email = $form->get('email')->getData();

            $hashedPassword = $passwordHasher->hashPassword($user, $form->get('password')->getData());
            $user->setEmail($email);
            $user->setPassword($hashedPassword);
            $user->setRoles(['ROLE_STAFF']);
            $user->setStaff($staff);


            $entityManager->persist($staff);
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Staff created successfully!');
            return $this->redirectToRoute('app_staff_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/staff/new.html.twig', [
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

    #[Route('/{id}/edit', name: 'app_staff_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Staff $staff, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(StaffType::class, $staff, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Staff updated successfully!');
            return $this->redirectToRoute('app_staff_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/staff/edit.html.twig', [
            'staff' => $staff,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_staff_delete', methods: ['POST'])]
    public function delete(Request $request, Staff $staff, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$staff->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($staff);
            $entityManager->flush();
            $this->addFlash('success', 'Staff deleted successfully!');
        }

        return $this->redirectToRoute('app_staff_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/user/{id}/reset-password', name: 'app_staff_reset_password')]
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
        return $this->redirectToRoute('app_staff_edit', ['id' => $user->getStaff()->getId()]);
    }
}
