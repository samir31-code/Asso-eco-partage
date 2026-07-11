<?php

namespace App\Controller\Admin;

use App\Entity\Outil;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class OutilCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Outil::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('nom', 'Nom de l\'outil');
        yield AssociationField::new('proprietaire', 'Propriétaire');

        // Ce champ permet de voir qui a l'outil.
        // Si l'admin supprime l'emprunteur ici, l'outil redevient disponible instantanément !
        yield AssociationField::new('emprunteur', 'Emprunteur Actuel')->setRequired(false);
    }
}
