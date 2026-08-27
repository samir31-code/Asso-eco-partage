<?php

namespace App\Service;

use App\Entity\Historique;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class NotificationService
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * 1. Notification à l'emprunteur pour confirmer que sa demande est enregistrée (en attente)
     */
    public function sendDemandeEnAttenteNotification(Historique $historique): void
    {
        $emprunteur = $historique->getUser();
        $outil = $historique->getOutil();

        $email = (new TemplatedEmail())
            ->from(new Address('ne-pas-repondre@eco-partage.fr', 'Eco-Partage'))
            ->to($emprunteur->getEmail())
            ->subject('Demande d\'emprunt enregistrée (en attente) - Eco-Partage')
            ->htmlTemplate('emails/emprunt_confirmation.html.twig')
            ->context([
                'user' => $emprunteur,
                'outil' => $outil,
                'historique' => $historique,
                'dateLimite' => (new \DateTimeImmutable())->modify('+7 days'),
            ]);

        $this->mailer->send($email);
    }

    /**
     * 2. Notification au propriétaire pour l'informer d'une nouvelle demande en attente
     */
    public function sendNotificationProprietaire(Historique $historique): void
    {
        $outil = $historique->getOutil();
        $proprietaire = $outil->getProprietaire();
        $emprunteur = $historique->getUser();

        if ($proprietaire && $proprietaire !== $emprunteur) {
            $email = (new TemplatedEmail())
                ->from(new Address('ne-pas-repondre@eco-partage.fr', 'Eco-Partage'))
                ->to($proprietaire->getEmail())
                ->subject('Nouvelle demande d\'emprunt pour votre outil ! - Eco-Partage')
                ->htmlTemplate('emails/notification_proprietaire.html.twig')
                ->context([
                    'proprietaire' => $proprietaire,
                    'emprunteur' => $emprunteur,
                    'outil' => $outil,
                    'historique' => $historique,
                ]);

            $this->mailer->send($email);
        }
    }

    /**
     * 3. E-mail de remerciement lors du retour de l'outil
     */
    public function sendRetourNotification(Historique $historique): void
    {
        $emprunteur = $historique->getUser();
        $outil = $historique->getOutil();

        $email = (new TemplatedEmail())
            ->from(new Address('ne-pas-repondre@eco-partage.fr', 'Eco-Partage'))
            ->to($emprunteur->getEmail())
            ->subject('Merci pour le retour de votre outil ! - Eco-Partage')
            ->htmlTemplate('emails/retour_remerciement.html.twig')
            ->context([
                'user' => $emprunteur,
                'outil' => $outil,
            ]);

        $this->mailer->send($email);
    }

    /**
     * 4. Relance manuelle ou automatique pour retard
     */
    public function sendRelanceRetard(Historique $historique): void
    {
        $emprunteur = $historique->getUser();
        $outil = $historique->getOutil();

        $email = (new TemplatedEmail())
            ->from(new Address('ne-pas-repondre@eco-partage.fr', 'Eco-Partage'))
            ->to($emprunteur->getEmail())
            ->subject('⚠️ Rappel : Retour d\'outil en retard - Eco-Partage')
            ->htmlTemplate('emails/relance_retard.html.twig')
            ->context([
                'user' => $emprunteur,
                'outil' => $outil,
                'dateLimite' => $historique->getDateDebut()->modify('+7 days'),
            ]);

        $this->mailer->send($email);
    }

    /**
     * 5. Notification à l'emprunteur lorsque le propriétaire VALIDE ou REFUSE la demande
     */
    public function sendReponseEmpruntNotification(Historique $historique): void
    {
        $emprunteur = $historique->getUser();
        $outil = $historique->getOutil();
        $statut = $historique->getStatut(); // 'valide' ou 'refuse'

        $subject = $statut === 'valide'
            ? '✅ Votre demande d\'emprunt a été acceptée ! - Eco-Partage'
            : '❌ Votre demande d\'emprunt a été refusée - Eco-Partage';

        $email = (new TemplatedEmail())
            ->from(new Address('ne-pas-repondre@eco-partage.fr', 'Eco-Partage'))
            ->to($emprunteur->getEmail())
            ->subject($subject)
            ->htmlTemplate('emails/emprunt_confirmation.html.twig')
            ->context([
                'user' => $emprunteur,
                'outil' => $outil,
                'historique' => $historique,
                'dateLimite' => $historique->getDateDebut()->modify('+7 days'),
            ]);

        $this->mailer->send($email);
    }
}
