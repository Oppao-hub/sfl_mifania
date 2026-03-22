<?php

namespace App\Controller\Dashboard\Admin;

use App\Entity\User;
use App\Form\AdminProfileType;
use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/account')]
class AdminAccountController extends AbstractController
{
    #[Route('', name: 'app_account_admin')]
    public function index(#[CurrentUser] User $user): Response
    {
        return $this->render('dashboard/account/profile_info.html.twig', [
            'user' => $user->getAdmin(),
        ]);
    }

    #[Route('/edit', name: 'app_account_admin_edit')]
    public function edit(Request $request, #[CurrentUser] User $user, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $admin = $user->getAdmin();
        $form = $this->createForm(AdminProfileType::class, $admin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('avatar')->getData();
            if ($imageFile) {
                $newFileName = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME)).'-'.uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move($this->getParameter('admin_images_directory'), $newFileName);
                $admin->setAvatar($newFileName);
            }
            $em->flush();
            $this->addFlash('success', 'Admin profile updated.');
            return $this->redirectToRoute('app_account_admin');
        }

        return $this->render('dashboard/account/edit_profile.html.twig', ['form' => $form]);
    }

    #[Route('/password', name: 'app_account_admin_password', methods: ['GET', 'POST'])]
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
            return $this->redirectToRoute('app_account_admin_password');
        }

        return $this->render('dashboard/account/password.html.twig', [
            'form' => $form->createView(),
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200));
    }
}
