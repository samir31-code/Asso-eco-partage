<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Outil;
use App\Entity\Categorie;
use App\Repository\UserRepository;
use App\Repository\OutilRepository;
use Symfony\UX\Chartjs\Model\Chart; // <-- Import du repository des catégories
use App\Repository\CategorieRepository;
use App\Repository\HistoriqueRepository;
use App\Controller\Admin\UserCrudController;
use App\Controller\Admin\OutilCrudController;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

#[IsGranted('ROLE_ADMIN')]
#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private UserRepository $userRepository,
        private OutilRepository $outilRepository,
        private HistoriqueRepository $historiqueRepository,
        private CategorieRepository $categorieRepository, // <-- Injection du CategorieRepository
        private ChartBuilderInterface $chartBuilder
    ) {}

    public function index(): Response
    {
        $totalUsers = $this->userRepository->count([]);
        $totalOutils = $this->outilRepository->count([]);
        $outilsEmpruntes = $this->outilRepository->countEmpruntes();
        $statsEmprunts = $this->historiqueRepository->findEmpruntsParMois();

        $empruntsRetard = $this->historiqueRepository->findEmpruntsEnRetard();
        $derniersEmprunts = $this->historiqueRepository->findDerniersEmprunts(5);
        $topOutils = $this->outilRepository->findTopOutils(5);

        $topLabels = [];
        $topData = [];

        foreach ($topOutils as $item) {
            $topLabels[] = $item['outil']->getNom();
            $topData[] = $item['nbEmprunts'];
        }

        // Création du graphique Chart.js pour le Top Outils
        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels' => $topLabels,
            'datasets' => [
                [
                    'label' => 'Nombre d\'emprunts',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.7)',
                    'borderColor' => 'rgb(37, 99, 235)',
                    'data' => $topData,
                ],
            ],
        ]);

        $chart->setOptions([
            'responsive' => true,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ]);

        // === OPTION 4 : Graphique de répartition des outils par catégorie (Couleurs synchronisées BDD) ===
        $outilsParCategorie = $this->outilRepository->countOutilsParCategorie();

        $catLabels = [];
        $catData = [];
        $catColors = [];

        foreach ($outilsParCategorie as $item) {
            $catLabels[] = $item['categorieNom'];
            $catData[] = $item['total'];

            // Recherche de la catégorie correspondante en BDD pour récupérer sa couleur fixe
            $categorieEntity = $this->categorieRepository->findOneBy(['nom' => $item['categorieNom']]);

            // On attribue la couleur de la BDD, ou une couleur par défaut (#95a5a6) si elle n'est pas définie
            $catColors[] = ($categorieEntity && $categorieEntity->getCouleur()) ? $categorieEntity->getCouleur() : '#95a5a6';
        }

        $chartCategorie = $this->chartBuilder->createChart(Chart::TYPE_PIE);
        $chartCategorie->setData([
            'labels' => $catLabels,
            'datasets' => [
                [
                    'label' => 'Outils par catégorie',
                    'backgroundColor' => $catColors, // <-- Utilisation des couleurs dynamiques issues de la BDD
                    'data' => $catData,
                ],
            ],
        ]);

        $chartCategorie->setOptions([
            'responsive' => true,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ]);
        // =================================================================

        $urlUsers = $this->generateUrl('admin', [
            'crudAction' => 'index',
            'crudControllerFqcn' => UserCrudController::class,
        ]);

        $urlOutils = $this->generateUrl('admin', [
            'crudAction' => 'index',
            'crudControllerFqcn' => OutilCrudController::class,
        ]);

        return $this->render('admin/dashboard.html.twig', [
            'total_users' => $totalUsers,
            'total_outils' => $totalOutils,
            'outils_empruntes' => $outilsEmpruntes,
            'stats_emprunts' => json_encode($statsEmprunts),
            'emprunts_retard' => $empruntsRetard,
            'derniers_emprunts' => $derniersEmprunts,
            'top_outils_labels' => json_encode($topLabels),
            'top_outils_data' => json_encode($topData),
            'url_users' => $urlUsers,
            'url_outils' => $urlOutils,
            'chart' => $chart,
            'chart_categorie' => $chartCategorie,
            'cat_labels' => json_encode($catLabels),
            'cat_data' => json_encode($catData),
            'cat_colors' => json_encode($catColors), // <-- Passage du tableau des couleurs à Twig
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Eco Partage');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Accueil', 'fa fa-home');
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fa fa-users');
        yield MenuItem::linkTo(OutilCrudController::class, 'Outils', 'fa fa-wrench');
    }
}
