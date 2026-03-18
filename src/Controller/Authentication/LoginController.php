<?php

namespace App\Controller\Authentication;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) { // If user is already logged in
            if (\in_array('ROLE_ADMIN', $this->getUser()->getRoles()) || \in_array('ROLE_STAFF', $this->getUser()->getRoles())) { // If user is an admin or staff
                $this->addFlash('success', 'You are already logged in!');
                return $this->redirectToRoute('app_dashboard'); // Redirect to admin dashboard
            } else { // If user is a regular user, redirect to home page
                $this->addFlash('success', 'You are already logged in!');
                return $this->redirectToRoute('app_home'); // Redirect to home page
            }
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        // Render the login form
        return $this->render('auth/login/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
