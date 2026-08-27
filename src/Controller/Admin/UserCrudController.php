<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        // 🆔 Identifiant unique
        yield IdField::new('id')->hideOnForm();

        // 👤 Informations Personnelles
        yield TextField::new('prenom', 'Prénom');
        yield TextField::new('nom', 'Nom');
        yield TextField::new('email', 'Adresse Email');

        // 📞 Téléphone
        yield TextField::new('telephone', 'Téléphone')
            ->setFormTypeOptions(['attr' => ['type' => 'tel']]);

        // 💳 Statut de cotisation (Visible directement dans la liste et modifiable)
        yield BooleanField::new('isCotise', 'Cotisation payée');

        // 📍 Adresse de facturation (Masquées sur l'index principal, visibles en édition/détails)
        yield TextField::new('adresse', 'Numéro et rue')->hideOnIndex();
        yield TextField::new('complementAdresse', 'Complément d\'adresse')->hideOnIndex();
        yield TextField::new('codePostal', 'Code Postal')->hideOnIndex();
        yield TextField::new('ville', 'Ville'); // Laisse la ville visible sur l'index pour situer l'utilisateur
        yield TextField::new('pays', 'Pays')->hideOnIndex();

        // 🔐 Sécurité & Rôles
        yield BooleanField::new('isVerified', 'Compte vérifié');

        yield ChoiceField::new('roles', 'Droits / Statut')
            ->allowMultipleChoices()
            ->setChoices([
                'Utilisateur (Simple)' => 'ROLE_USER',
                'Membre (Cotisation à jour) ✅' => 'ROLE_MEMBRE',
                'Administrateur 🛡️' => 'ROLE_ADMIN',
            ]);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('isCotise', 'À jour de cotisation'))
        ;
    }
}
