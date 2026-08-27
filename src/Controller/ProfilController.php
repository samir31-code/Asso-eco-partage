<?php

namespace App\Controller;

use App\Repository\OutilRepository;
use App\Repository\HistoriqueRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProfilController extends AbstractController
{
    #[Route('/profil', name: 'app_profil')]
    #[IsGranted('ROLE_USER')]
    public function index(HistoriqueRepository $historiqueRepository, OutilRepository $outilRepository): Response
    {
        $user = $this->getUser();

        // 🛡️ SÉCURITÉ : Si c'est un utilisateur en mémoire (ex: compte jury), on évite les requêtes Doctrine
        if (!$user instanceof \App\Entity\User) {
            return $this->render('profil/index.html.twig', [
                'demandesEnAttente' => [],
                'empruntsEnCours' => [],
                'empruntsTermines' => [],
                'mesOutils' => [],
            ]);
        }

        // 0. Demandes en attente (que l'utilisateur soit le demandeur ou le propriétaire de l'outil)
        $demandesEnAttente = $historiqueRepository->findDemandesEnAttenteByUser($user);

        // 1. Emprunts actuellement en cours (Uniquement ceux qui sont VALIDÉS)
        $empruntsEnCours = $historiqueRepository->findEmpruntsEnCoursByUser($user);

        // 2. Historique des emprunts terminés
        $empruntsTermines = $historiqueRepository->findBy([
            'user' => $user,
        ], ['dateFin' => 'DESC']);

        $empruntsTermines = array_filter($empruntsTermines, function($h) {
            return $h->getDateFin() !== null;
        });

        // 3. Mes outils mis en ligne
        $mesOutils = $outilRepository->findBy([
            'proprietaire' => $user
        ], ['nom' => 'ASC']);

        return $this->render('profil/index.html.twig', [
            'demandesEnAttente' => $demandesEnAttente,
            'empruntsEnCours' => $empruntsEnCours,
            'empruntsTermines' => $empruntsTermines,
            'mesOutils' => $mesOutils,
        ]);
    }
}
