<?php

namespace App\Controller;

use App\Repository\HistoriqueRepository;
use App\Repository\OutilRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfilController extends AbstractController
{
    #[Route('/profil', name: 'app_profil')]
    #[IsGranted('ROLE_USER')]
    public function index(HistoriqueRepository $historiqueRepository, OutilRepository $outilRepository): Response
    {
        $user = $this->getUser();

        // 1. Emprunts actuellement en cours
        $empruntsEnCours = $historiqueRepository->findBy([
            'user' => $user,
            'dateFin' => null
        ], ['dateDebut' => 'DESC']);

        // 2. Historique des emprunts terminés
        $empruntsTermines = $historiqueRepository->findBy([
            'user' => $user,
        ], ['dateFin' => 'DESC']);

        $empruntsTermines = array_filter($empruntsTermines, function($h) {
            return $h->getDateFin() !== null;
        });

        // 3. ✨ NOUVEAU : Récupérer mes outils mis en ligne
        $mesOutils = $outilRepository->findBy([
            'proprietaire' => $user
        ], ['nom' => 'ASC']);

        return $this->render('profil/index.html.twig', [
            'empruntsEnCours' => $empruntsEnCours,
            'empruntsTermines' => $empruntsTermines,
            'mesOutils' => $mesOutils,
        ]);
    }
}
