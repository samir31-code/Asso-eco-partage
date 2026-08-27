<?php

namespace App\Controller;

use App\Repository\OutilRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(OutilRepository $outilRepository): Response
    {
        // On récupère par exemple les 6 derniers outils enregistrés
        $derniersOutils = $outilRepository->findBy([], ['id' => 'DESC'], 6);

        return $this->render('home/index.html.twig', [
            'derniers_outils' => $derniersOutils,
        ]);
    }
}
