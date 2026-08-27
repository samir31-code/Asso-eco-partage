<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260724220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création ou mise à jour de la table historique';
    }

    public function up(Schema $schema): void
    {
        // Ajoute ici tes requêtes SQL si nécessaire (ou laisse vide si la table est déjà créée et que tu veux juste aligner l'historique)
    }

    public function down(Schema $schema): void
    {
        // Instructions pour annuler la migration si besoin
    }
}
