<?php

namespace App\Controller;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaiementController extends AbstractController
{
    #[Route('/cotisation/paiement', name: 'app_cotisation_paiement')]
    public function payerCotisation(): Response
    {
        $stripeSecretKey = $_ENV['STRIPE_SECRET_KEY'] ?? $this->getParameter('stripe_secret_key');
        Stripe::setApiKey($stripeSecretKey);

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Cotisation Annuelle - Association Eco-Partage',
                    ],
                    'unit_amount' => 1500, // 15,00 €
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('app_paiement_succes', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('app_paiement_annule', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return $this->redirect($session->url, 303);
    }

    #[Route('/cotisation/succes', name: 'app_paiement_succes')]
    public function paiementSucces(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if ($user) {
            $roles = $user->getRoles();

            if (!in_array('ROLE_MEMBRE', $roles)) {
                $roles[] = 'ROLE_MEMBRE';
                $user->setRoles($roles);
                $entityManager->flush();
            }
        }

        return $this->render('paiement/succes.html.twig');
    }

    #[Route('/cotisation/annule', name: 'app_paiement_annule')]
    public function paiementAnnule(): Response
    {
        return $this->render('paiement/annule.html.twig');
    }
}
