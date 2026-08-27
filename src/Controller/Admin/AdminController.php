<?php

namespace App\Controller\Admin;

use App\Entity\Outil;
use App\Service\NotificationService;
use App\Repository\HistoriqueRepository;
use App\Controller\Admin\OutilCrudController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/admin/relancer-outil/{id}', name: 'admin_relancer_outil')]
    public function relancerOutil(
        Outil $outil,
        HistoriqueRepository $historiqueRepository,
        NotificationService $notifier,
        AdminUrlGenerator $adminUrlGenerator
    ): Response {
        $historiqueActif = $historiqueRepository->findOneBy([
            'outil' => $outil,
            'dateFin' => null
        ]);

        if (!$historiqueActif) {
            $this->addFlash('warning', 'Cet outil n\'est pas en cours d\'emprunt.');
            return $this->redirect($adminUrlGenerator->setController(OutilCrudController::class)->generateUrl());
        }

        $notifier->sendRelanceRetard($historiqueActif);

        $this->addFlash('success', sprintf(
            'E-mail de relance envoyé avec succès à %s pour l\'outil "%s" !',
            $historiqueActif->getUser()->getEmail(),
            $outil->getNom()
        ));

        return $this->redirect($adminUrlGenerator->setController(OutilCrudController::class)->generateUrl());
    }
}
