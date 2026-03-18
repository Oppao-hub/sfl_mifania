<?php

namespace App\Controller\Frontend;

use App\Service\ContactMailerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, ContactMailerService $contactMailer): Response
    {
        // Check if the user just submitted the form
        if ($request->isMethod('POST')) {
            // Grab the data from the HTML form fields
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $subject = $request->request->get('subject');
            $message = $request->request->get('message');

            // Fire off the email using your new service!
            $contactMailer->sendContactMessage($name, $email, $subject, $message);

            // Add a success banner to show the user
            $this->addFlash('success', 'Thank you! Your message has been sent. We will get back to you shortly.');

            // Refresh the page so they don't accidentally submit the form twice
            return $this->redirectToRoute('app_contact');
        }

        // If they just landed on the page normally, show the form
        return $this->render('frontend/contact/index.html.twig');
    }
}
