<?php

namespace App\Controller\Authentication;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\EmailVerificationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class EmailVerificationController extends AbstractController
{
    #[Route('/email-verification', name: 'app_email_verification')]
    public function verifyUserEmail(
        Request $request,
        EmailVerificationService $emailVerificationService
    ): Response {
        $token = $request->query->get('token');

        if (!$token) {
            $this->addFlash('error', 'Verification token is missing.');
            return $this->redirectToRoute('app_register');
        }

        $user = $emailVerificationService->verifyToken($token);

        if (!$user) {
            $this->addFlash('error', 'The verification link is invalid or has expired. Please request a new one below.');
            return $this->redirectToRoute('app_resend_verification');
        }


        $this->addFlash('success', 'Your email has been verified! You can now log in.');

        return $this->redirectToRoute('app_login');
    }

    #[Route('/resend-verification', name: 'app_resend_verification')]
    public function resendVerification(
        Request $request,
        EmailVerificationService $emailVerificationService,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('POST')) {
            $token = $request->request->get('_csrf_token');
            if (!$this->isCsrfTokenValid('resend_verification', $token)) {
                $this->addFlash('error', 'Invalid security token. Please try again.');
                return $this->redirectToRoute('app_resend_verification');
            }

            $email = trim($request->request->get('email', ''));

            if (empty($email)) {
                $this->addFlash('error', 'Please enter your email address.');
                return $this->redirectToRoute('app_resend_verification');
            }

            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user) {
                if ($user->getIsVerified()) {
                    $this->addFlash('info', 'This account is already verified. You can log in.');
                    return $this->redirectToRoute('app_login');
                }

                // Generate new token
                $newToken = $emailVerificationService->generateVerificationToken();
                $user->setVerificationToken($newToken);
                $entityManager->flush();

                // Generate URL
                $verificationUrl = $this->generateUrl(
                    'app_email_verification',
                    ['token' => $newToken],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                try {
                    $emailVerificationService->sendVerificationEmail($user, $verificationUrl);
                    return $this->redirectToRoute('app_resend_verification_success');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'There was an issue sending the email. Please try again later.');
                }
            } else {
                // For security, still redirect to success page to avoid email harvesting
                return $this->redirectToRoute('app_resend_verification_success');
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/verification/resend.html.twig');
    }

    #[Route('/resend-verification/success', name: 'app_resend_verification_success')]
    public function resendSuccess(): Response
    {
        return $this->render('auth/verification/success.html.twig');
    }
}
