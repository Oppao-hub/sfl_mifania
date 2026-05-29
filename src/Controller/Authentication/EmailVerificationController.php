<?php

namespace App\Controller\Authentication;

use App\Service\EmailVerificationResendService;
use App\Service\EmailVerificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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

        return $this->render('auth/verification/verification_success.html.twig');
    }

    #[Route('/resend-verification', name: 'app_resend_verification')]
    public function resendVerification(
        Request $request,
        EmailVerificationResendService $emailVerificationResendService,
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

            if ($email === '') {
                $this->addFlash('error', 'Please enter your email address.');
                return $this->redirectToRoute('app_resend_verification');
            }

            try {
                $emailVerificationResendService->resendForEmail($email);
            } catch (\Exception) {
                $this->addFlash('error', 'There was an issue sending the email. Please try again later.');

                return $this->redirectToRoute('app_resend_verification');
            }

            return $this->redirectToRoute('app_resend_verification_success');
        }

        return $this->render('auth/verification/resend.html.twig');
    }

    #[Route('/resend-verification/success', name: 'app_resend_verification_success')]
    public function resendSuccess(): Response
    {
        return $this->render('auth/verification/success.html.twig');
    }
}
