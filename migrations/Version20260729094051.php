<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260729094051 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE categorie ADD couleur VARCHAR(7) DEFAULT NULL');
        $this->addSql('ALTER TABLE historique ADD CONSTRAINT FK_EDBFD5EC3ED89C80 FOREIGN KEY (outil_id) REFERENCES outil (id)');
        $this->addSql('ALTER TABLE historique ADD CONSTRAINT FK_EDBFD5ECA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE outil ADD CONSTRAINT FK_22627A3EBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE outil ADD CONSTRAINT FK_22627A3E76C50E4A FOREIGN KEY (proprietaire_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE outil ADD CONSTRAINT FK_22627A3EF0840037 FOREIGN KEY (emprunteur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B784183ED89C80 FOREIGN KEY (outil_id) REFERENCES outil (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE categorie DROP couleur');
        $this->addSql('ALTER TABLE historique DROP FOREIGN KEY FK_EDBFD5EC3ED89C80');
        $this->addSql('ALTER TABLE historique DROP FOREIGN KEY FK_EDBFD5ECA76ED395');
        $this->addSql('ALTER TABLE outil DROP FOREIGN KEY FK_22627A3EBCF5E72D');
        $this->addSql('ALTER TABLE outil DROP FOREIGN KEY FK_22627A3E76C50E4A');
        $this->addSql('ALTER TABLE outil DROP FOREIGN KEY FK_22627A3EF0840037');
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B784183ED89C80');
    }
}
