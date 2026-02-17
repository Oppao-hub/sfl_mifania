<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\User;
use App\Entity\Wallet;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        Security $security,
        EntityManagerInterface $entityManager,
        VerifyEmailHelperInterface $verifyEmailHelper,
        MailerInterface $mailer,
    ): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // set user
            $user->setEmail($form->get('email')->getData());
            $user->setRoles(['ROLE_CUSTOMER']);
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $customer = new Customer();
            $customer->setUser($user);
            $customer->setFirstName($form->get('firstName')->getData());
            $customer->setLastName($form->get('lastName')->getData());

            $wallet = new Wallet();
            $wallet->setBalance(0.00);
            $wallet->setRewardPoints(0);
            $wallet->setCustomer($customer);

            $entityManager->persist($user);
            $entityManager->persist($customer);
            $entityManager->persist($wallet);
            $entityManager->flush();

            //generate the signed URL
            $signatureComponents = $verifyEmailHelper->generateSignature(
                'app_verify_email',
                $user->getId(),
                $user->getEmail(),
                ['id' => $user->getId()],
            );

            //send email
            $email = (new TemplatedEmail())
                ->from(new Address('mifaniapaolo0012@gmail.com', 'Mifania Sustainable Fashion Line'))
                ->to($user->getEmail())
                ->subject('Please Confirm your Email')
                ->htmlTemplate('registration/email_confirmation.html.twig')
                ->context([
                    'signedUrl' => $signatureComponents->getSignedUrl(),
                    'expiresAtMessageKey' => $signatureComponents->getExpirationMessageKey(),
                    'expiresAtMessageData' => $signatureComponents->getExpirationMessageData(),
                ]);

            //Force error display if it fails
            try {
                $mailer->send($email);
            } catch (\Exception $e) {
                dd(
                    'EMAIL ERROR:',
                    $e->getMessage(),
                    'Trace:',
                    $e->getTraceAsString()
                );
            }

            $this->addFlash('success', 'Registration successful! Please check your email to verify your account.');

            // Redirect to Login instead of auto-logging in
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
        VerifyEmailHelperInterface $verifyEmailHelper,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
    ): Response
    {
        $userId = $request->get('id');
        if (null === $userId) {
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($userId);

        if(null === $user) {
            return $this->redirectToRoute('app_register');
        }

        //validate the link
        try {
            $verifyEmailHelper->validateEmailConfirmationFromRequest(
                $request,
                $user->getId(),
                $user->getEmail(),
            );
        } catch (\Exception $e) {
            $this->addFlash('verify_email_error', $e->getMessage());

            return $this->redirectToRoute('app_register');
        }

        //mark as verified
        $user->setIsVerified(true);
        $entityManager->flush();

        $this->addFlash('success', 'Your email address has been verified. You can now log in.');

        return $this->redirectToRoute('app_login');
    }
}
