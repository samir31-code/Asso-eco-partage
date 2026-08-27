<?php

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LegalController extends AbstractController
{
    #[Route('/mentions-legales', name: 'app_mentions_legales')]
    public function mentionsLegales(): Response
    {
        return $this->render('legal/mentions_legales.html.twig');
    }

    #[Route('/politique-confidentialite', name: 'app_confidentialite')]
    public function confidentialite(): Response
    {
        return $this->render('legal/confidentialite.html.twig');
    }

    #[Route('/qui-sommes-nous', name: 'app_qui_sommes_nous')]
    public function quiSommesNous(): Response
    {
        return $this->render('legal/qui_sommes_nous.html.twig');
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Création et envoi de l'e-mail (intercepté par Mailpit en local)
            $email = (new Email())
                ->from($data['email'])
                ->to('sam@sam.fr')
                ->subject('Nouveau message [Eco-Partage] - ' . $data['subject'])
                ->text(
                    "Prénom et nom : " . $data['fullName'] . "\n" .
                    "E-mail : " . $data['email'] . "\n" .
                    "N° de compte : " . ($data['accountNumber'] ?? 'Non renseigné') . "\n\n" .
                    "Message :\n" . $data['message']
                );

            $mailer->send($email);

            $this->addFlash('success', 'Votre message a bien été envoyé. Notre équipe vous répondra rapidement !');
            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig', [
            'contactForm' => $form->createView(),
        ]);
    }

    #[Route('/conditions-generales-de-vente', name: 'app_cgv')]
    public function cgv(): Response
    {
        return $this->render('legal/cgv.html.twig');
    }
}