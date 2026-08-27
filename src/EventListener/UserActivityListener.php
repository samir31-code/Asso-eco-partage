<?php

namespace App\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsEventListener(event: 'kernel.request')]
class UserActivityListener
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private EntityManagerInterface $em
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $token = $this->getTokenStorage()->getToken();
        if (!$token) {
            return;
        }

        $user = $token->getUser();
        if ($user instanceof \App\Entity\User) {
            // Utilisation de DateTimeImmutable pour correspondre au type attendu par l'entité User
            $now = new \DateTimeImmutable();
            if (!$user->getDerniereActivite() || $user->getDerniereActivite()->diff($now)->i >= 2) {
                $user->setDerniereActivite($now);
                $this->em->flush();
            }
        }
    }

    private function getTokenStorage(): TokenStorageInterface
    {
        return $this->tokenStorage;
    }
}
