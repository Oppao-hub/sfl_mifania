<?php

namespace App\Controller;

use App\Form\CustomerType;
use App\Form\EditProfileType;
use App\Form\ChangePasswordType;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class AccountController extends AbstractController
{
    #[Route('/account', name: 'app_account')]
    public function index(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // 1. Logic for Admins and Staff
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {

            // Determine the specific profile entity
            $profile = $this->isGranted('ROLE_ADMIN') ? $user->getAdmin() : $user->getStaff();

            return $this->render('dashboard/account/profile_info.html.twig', [
                'user' => $profile,
            ]);
        }

        // 2. Logic for Customers (Default)
        return $this->render('customer/account/profile_info.html.twig', [
            'user' => $user->getCustomer(),
        ]);
    }

    #[Route('/account/edit', name: 'app_account_edit')]
    public function editProfile(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        ActivityLogger $activityLogger
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // --- DYNAMIC CONFIGURATION BASED ON ROLE ---
        $targetEntity = null;
        $formClass = null;
        $directoryParam = null; // Renamed for clarity
        $viewTemplate = null;

        if ($this->isGranted('ROLE_ADMIN')) {
            $targetEntity = $user->getAdmin();
            $formClass = EditProfileType::class;
            $directoryParam = 'admin_images_directory'; // <--- Simple Name
            $viewTemplate = 'dashboard/account/edit_profile.html.twig';

        } elseif ($this->isGranted('ROLE_STAFF')) {
            $targetEntity = $user->getStaff();
            $formClass = EditProfileType::class;
            $directoryParam = 'staff_images_directory'; // <--- Simple Name
            $viewTemplate = 'dashboard/account/edit_profile.html.twig';

        } else {
            $targetEntity = $user->getCustomer();
            $formClass = CustomerType::class;
            $directoryParam = 'customer_images_directory'; // <--- Simple Name
            $viewTemplate = 'customer/account/edit_profile.html.twig';
        }

        if (!$targetEntity) {
            $this->addFlash('error', 'Profile data not found.');
            return $this->redirectToRoute('app_account');
        }

        // --- FORM HANDLING ---
        $form = $this->createForm($formClass, $targetEntity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Handle Avatar Upload
            $imageFile = $form->get('avatar')->getData();
            if ($imageFile) {
                $originalFileName = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFileName = $slugger->slug($originalFileName);
                $newFileName = $safeFileName . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    // Move to the directory specific to the Role
                    $imageFile->move(
                        $this->getParameter($directoryParam),
                        $newFileName
                    );

                    // Optional: Delete old avatar if necessary
                    $targetEntity->setAvatar($newFileName);

                } catch (FileException $e) {
                    $this->addFlash('error', 'Image upload failed: ' . $e->getMessage());
                }
            }

            $em->persist($targetEntity);
            $em->flush();

            $activityLogger->log('UPDATE', "User {$user->getId()} updated their profile details.", $user);

            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('app_account');
        }

        return $this->render($viewTemplate, [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/account/wallet', name: 'app_account_wallet')]
    public function wallet(): Response
    {
        // STRICTLY CUSTOMER ONLY
        if (!$this->isGranted('ROLE_CUSTOMER') || $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
            throw new AccessDeniedException('Wallets are for customers only.');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $customer = $user->getCustomer();
        $wallet = $customer ? $customer->getWallet() : null;

        return $this->render('customer/wallet/wallet.html.twig', [
            'user' => $user,
            'wallet' => $wallet,
        ]);
    }

    #[Route('/account/password', name: 'app_account_password')]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Determine template based on role
        $template = ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF'))
            ? 'dashboard/account/password.html.twig'
            : 'customer/account/password.html.twig';

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $current = $form->get('currentPassword')->getData();
            if (!$passwordHasher->isPasswordValid($user, $current)) {
                $this->addFlash('error', 'Current password is incorrect.');
            } else {
                $newPlain = $form->get('plainPassword')->getData();
                $hashed = $passwordHasher->hashPassword($user, $newPlain);
                $user->setPassword($hashed);
                $em->flush();

                $this->addFlash('success', 'Your password has been changed.');
                return $this->redirectToRoute('app_account_password');
            }
        }

        return $this->render($template, [
            'form' => $form->createView(),
        ]);
    }
}
