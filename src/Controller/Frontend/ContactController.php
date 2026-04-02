<?php

namespace App\Controller\Frontend;

use App\Form\ContactType;
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
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();

            $contactMailer->sendContactMessage(
                $data['name'],
                $data['email'],
                $data['subject'],
                $data['message'],
            );

            $this->addFlash('success', 'Thank you! Your message has been sent. We will get back to you shortly.');
            return $this->redirectToRoute('app_contact_success');
        }

        return $this->render('frontend/contact/index.html.twig',[
            'form' => $form->createView(),
        ])->setStatusCode($form->isSubmitted() && !$form->isValid() ? 422 : 200);
    }

    #[Route('/contact/success', name: 'app_contact_success')]
    public function success(): Response
    {
        return $this->render('frontend/contact/success.html.twig');
    }
}
