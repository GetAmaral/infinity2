<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

final class PublicController extends AbstractController
{
    #[Route('/sobre', name: 'public_about')]
    #[Route('/about', name: 'public_about_en')]
    public function about(): Response
    {
        return $this->render('public/about.html.twig');
    }

    #[Route('/solucoes', name: 'public_solutions')]
    #[Route('/solutions', name: 'public_solutions_en')]
    public function solutions(): Response
    {
        return $this->render('public/solutions.html.twig');
    }

    #[Route('/produtos', name: 'public_products')]
    #[Route('/products', name: 'public_products_en')]
    public function products(): Response
    {
        return $this->render('public/products.html.twig');
    }

    #[Route('/contato', name: 'public_contact', methods: ['GET', 'POST'])]
    #[Route('/contact', name: 'public_contact_en', methods: ['GET', 'POST'])]
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $phone = $request->request->get('phone', '');
            $message = $request->request->get('message');
            $csrfToken = $request->request->get('_token');

            // CSRF validation
            if (!$this->isCsrfTokenValid('contact_form', $csrfToken)) {
                $this->addFlash('error', 'contact.invalid_token');
                return $this->redirectToRoute('public_contact');
            }

            // Basic validation
            if (empty($name) || empty($email) || empty($message)) {
                $this->addFlash('error', 'contact.required_fields');
                return $this->redirectToRoute('public_contact');
            }

            try {
                // Send email to contact@avelum.com.br
                $emailMessage = (new Email())
                    ->from('noreply@avelum.com.br')
                    ->replyTo($email)
                    ->to('contact@avelum.com.br')
                    ->subject('Novo Contato - Website Avelum')
                    ->html($this->renderView('emails/contact.html.twig', [
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'message' => $message,
                    ]));

                $mailer->send($emailMessage);

                $this->addFlash('success', 'contact.message_sent');
                return $this->redirectToRoute('public_contact');
            } catch (\Exception $e) {
                $this->addFlash('error', 'contact.message_failed');
                return $this->redirectToRoute('public_contact');
            }
        }

        return $this->render('public/contact.html.twig');
    }

    #[Route('/privacidade', name: 'public_privacy')]
    #[Route('/privacy', name: 'public_privacy_en')]
    public function privacy(): Response
    {
        return $this->render('public/privacy.html.twig');
    }

    #[Route('/educacao', name: 'public_education')]
    #[Route('/education', name: 'public_education_en')]
    public function education(): Response
    {
        return $this->render('public/education.html.twig');
    }
}
