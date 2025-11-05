<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\User;
use App\Entity\Wallet;
use App\Form\UserCredentialsType;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/register')]
class RegistrationController extends AbstractController
{
    #[Route('/credentials', name: 'app_register_credentials')]
    public function credentials(
        Request $request,
        SessionInterface $session
    ): Response {
        $form = $this->createForm(UserCredentialsType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $plainPassword = $form->get('plainPassword')->getData();


            // Store credentials in session
            $session->set('register_email', $email);
            $session->set('register_password', $plainPassword);

            //redirect personal info
            return $this->redirectToRoute('app_register_personal_info');
        } else {
            $this->addFlash('error', 'Please correct the highlighted fields.');
        }

        return $this->render('registration/credentials.html.twig', [
            'credentialsForm' => $form->createView(),
        ]);
    }


    #[Route('/personalInfo', name: 'app_register_personal_info')]
    public function personalInfo(
        Request $request,
        EntityManagerInterface $em,
        SessionInterface $session,
        SluggerInterface $slugger,
        UserPasswordHasherInterface $hasher
    ): Response {

        $email = $session->get('register_email');
        $plainPassword = $session->get('register_password');

        if (!$email || !$plainPassword) {
            //if session is empty
            return $this->redirectToRoute('app_register_credentials');
        }

        $customer = new Customer();
        $form = $this->createForm(RegistrationFormType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
                        $this->getParameter('customer_images_directory'),
                        $newFileName
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Could not upload avatar. Please try again.');
                    return $this->render('registration/personalInfo.html.twig', [
                        'personalInfoForm' => $form->createView(),
                    ]);
                }
                $customer->setAvatar($newFileName);
            } else {
                $customer->setAvatar('No Avatar Yet');
            }
            //create and link the user
            $user = new User();
            $user->setEmail($email);
            $user->setRoles(['ROLE_USER']);
            $user->setPassword(
                $hasher->hashPassword($user, $plainPassword)
            );

            //create and link the wallet
            $wallet = new Wallet();
            $wallet->setBalance(0.00);
            $wallet->setRewardPoints(0);

            //link both sides
            $customer->setUser($user);
            $wallet->setCustomer($customer);
            $user->setCustomer($customer);

            $em->persist($customer);
            $em->persist($user);
            $em->persist($wallet);
            $em->flush();

            //clear session
            $session->remove('register_email');
            $session->remove('register_password');

            // do anything else you need here, like send an email
            $this->addFlash('success', 'Your account has been created.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/personalInfo.html.twig', [
            'personalInfoForm' => $form->createView(),
        ]);
    }
}
