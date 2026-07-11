<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Outil;
use App\Repository\UserRepository;
use App\Repository\OutilRepository;
use App\Controller\Admin\UserCrudController;    // 📦 Import des repos
use App\Controller\Admin\OutilCrudController;     // 📦 Import des repos
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator,
        private UserRepository $userRepository,   // ✨ Injection du repo User
        private OutilRepository $outilRepository  // ✨ Injection du repo Outil
    ) {}

    public function index(): Response
    {
        // 📊 On récupère les compteurs dynamiques
        $totalUsers = $this->userRepository->count([]);
        $totalOutils = $this->outilRepository->count([]);

        // On compte combien d'outils ont un emprunteur actuellement
        $outilsEmpruntes = $this->outilRepository->count(['emprunteur' => !null]);

        // 🎯 On envoie toutes ces variables à notre template Twig
        return $this->render('admin/dashboard.html.twig', [
            'total_users' => $totalUsers,
            'total_outils' => $totalOutils,
            'outils_empruntes' => $outilsEmpruntes,

            // Liens générés pour nos boutons d'accès rapide
            'url_users' => $this->adminUrlGenerator->setController(UserCrudController::class)->generateUrl(),
            'url_outils' => $this->adminUrlGenerator->setController(OutilCrudController::class)->generateUrl(),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Eco Partage - Administration');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');
        yield MenuItem::section('Gestion de l\'association');

        yield MenuItem::linkToUrl('Utilisateurs', 'fas fa-users',
            $this->adminUrlGenerator->setController(UserCrudController::class)->generateUrl());

        yield MenuItem::linkToUrl('Matériel / Outils', 'fas fa-tools',
            $this->adminUrlGenerator->setController(OutilCrudController::class)->generateUrl());
    }
}
