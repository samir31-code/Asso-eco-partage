<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfilController extends AbstractController
{
    #[Route('/profil', name: 'app_profil')]
    #[IsGranted('ROLE_USER')] // Bloque l'accès aux utilisateurs anonymes
    public function index(): Response
    {
        // On récupère l'utilisateur connecté
        $user = $this->getUser();

        // Grâce aux relations dans ton entité User.php :
        // Ses outils prêtés s'obtiennent via $user->getOutils()
        // Ses emprunts en cours s'obtiennent via $user->getEmprunts()

        return $this->render('profil/index.html.twig', [
            'user' => $user,
        ]);
    }
}
