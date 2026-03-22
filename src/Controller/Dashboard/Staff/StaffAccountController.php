<?php

namespace App\Controller\Dashboard\Staff;

use App\Entity\User;
use App\Form\StaffProfileType;
use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/staff/account')]
class StaffAccountController extends AbstractController
{
    #[Route('', name: 'app_staff_account')]
    public function index(#[CurrentUser] User $user): Response
    {
        return $this->render('dashboard/account/profile_info.html.twig', [
            'user' => $user->getStaff(),
        ]);
    }

    #[Route('/edit', name: 'app_staff_account_edit')]
    public function edit(Request $request, #[CurrentUser] User $user, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $staff = $user->getStaff();
        $form = $this->createForm(StaffProfileType::class, $staff);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('avatar')->getData();
            if ($imageFile) {
                $newFileName = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME)).'-'.uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move($this->getParameter('staff_images_directory'), $newFileName);
                $staff->setAvatar($newFileName);
            }
            $em->flush();
            $this->addFlash('success', 'Staff profile updated.');
            return $this->redirectToRoute('app_staff_account');
        }

        return $this->render('dashboard/account/edit_profile.html.twig', ['form' => $form]);
    }

    #[Route('/password', name: 'app_staff_account_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        #[CurrentUser] User $user,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(ChangePasswordType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
                $newPlain = $form->get('plainPassword')->getData();
                $hashed = $passwordHasher->hashPassword($user, $newPlain);
                $user->setPassword($hashed);
                $em->flush();

                $this->addFlash('success', 'Your password has been changed.');
                return $this->redirectToRoute('app_staff_account_password');
        }

        return $this->render('dashboard/account/password.html.twig', [
            'form' => $form->createView(),
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200) );
    }
}
