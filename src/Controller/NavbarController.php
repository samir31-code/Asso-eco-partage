<?php

namespace App\Controller;

use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class NavbarController extends AbstractController
{
    public function notificationBadge(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $count = 0;

        // On vérifie que l'utilisateur est bien une instance de notre entité User classique
        // et qu'il ne s'agit pas d'un utilisateur en mémoire (comme le jury)
        if ($user instanceof \App\Entity\User) {
            $count = $em->getRepository(Message::class)->count([
                'destinataire' => $user,
                'lu' => false
            ]);
        }

        return $this->render('components/_nav_messagerie.html.twig', [
            'unread_count' => $count,
        ]);
    }
}
