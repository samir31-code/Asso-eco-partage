<?php

namespace App\Controller\Admin;

use App\Entity\Outil;
use App\Entity\Categorie;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class OutilCrudController extends AbstractCrudController
{
    private $entityManager;
    private $adminUrlGenerator;

    public function __construct(EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator)
    {
        $this->entityManager = $entityManager;
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    public static function getEntityFqcn(): string
    {
        return Outil::class;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Outil) {
            if ($entityInstance->getProprietaire() === null) {
                $user = $this->getUser();
                $entityInstance->setProprietaire($user);
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('nom', 'Nom de l\'outil'),
            AssociationField::new('categorie', 'Catégorie'),
            TextField::new('etat', 'État'),

            ImageField::new('image', 'Photo de l\'outil')
                ->setBasePath('uploads/outils')
                ->setUploadDir('public/uploads/outils')
                ->setUploadedFileNamePattern('[randomhash].[extension]')
                ->setRequired(false),

            AssociationField::new('emprunteur', 'Emprunteur Actuel')
                ->setRequired(false),
            AssociationField::new('proprietaire', 'Propriétaire')
                ->onlyOnIndex(),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('emprunteur', 'Statut de l\'emprunt'));
    }

    public function configureActions(Actions $actions): Actions
    {
        // Bouton : Forcer le retour
        $forcerRetour = Action::new('forcerRetour', 'Forcer le retour 🤝', 'fas fa-undo-alt')
            ->linkToCrudAction('forcerRetourAction')
            ->setCssClass('btn btn-sm btn-outline-warning')
            ->displayIf(static function (Outil $outil) {
                return $outil->getEmprunteur() !== null;
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $forcerRetour)
            ->add(Crud::PAGE_DETAIL, $forcerRetour)
            ->setPermission('forcerRetour', 'ROLE_ADMIN');
    }

    #[AdminRoute(path: '/{id}/forcer-retour')]
    public function forcerRetourAction(AdminContext $context, MailerInterface $mailer): Response
    {
        /** @var Outil $outil */
        $outil = $context->getEntity()->getInstance();
        $emprunteur = $outil->getEmprunteur();

        if ($emprunteur !== null) {
            $emailEmprunteur = $emprunteur->getEmail();
            $nomEmprunteur = $emprunteur->getPrenom();
            $nomOutil = $outil->getNom();

            $outil->setEmprunteur(null);
            $this->entityManager->flush();

            try {
                $email = (new Email())
                    ->from('ne-pas-repondre@eco-partage.fr')
                    ->to($emailEmprunteur)
                    ->subject('Eco-Partage 🤝 - Retour de votre emprunt enregistré')
                    ->html("
                        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                            <h2 style='color: #198754;'>Bonjour {$nomEmprunteur},</h2>
                            <p>L'administration d'<strong>Eco-Partage</strong> vous informe que le retour de l'outil suivant a bien été enregistré :</p>
                            <p style='font-size: 1.2em; font-weight: bold; color: #495057; padding-left: 15px; border-left: 4px solid #198754;'>
                                🛠️ {$nomOutil}
                            </p>
                            <p>Cet outil est à présent répertorié comme disponible et peut être emprunté par d'autres membres de l'association.</p>
                            <p>Merci pour votre participation à la communauté d'entraide !</p>
                        </div>
                    ");

                $mailer->send($email);
                $this->addFlash('success', sprintf('Le retour de l\'outil "%s" a été forcé et un email a été envoyé.', $nomOutil));
            } catch (\Exception $e) {
                $this->addFlash('warning', sprintf('Le retour de l\'outil "%s" a été forcé, mais l\'email n\'a pas pu être envoyé.', $nomOutil));
            }
        } else {
            $this->addFlash('warning', 'Cet outil n\'est pas en cours d\'emprunt.');
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }
}
