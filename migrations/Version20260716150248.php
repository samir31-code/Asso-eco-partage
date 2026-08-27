<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260716150248 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE historique CHANGE date_fin date_fin DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE historique ADD CONSTRAINT FK_EDBFD5EC3ED89C80 FOREIGN KEY (outil_id) REFERENCES outil (id)');
        $this->addSql('ALTER TABLE historique ADD CONSTRAINT FK_EDBFD5ECA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE outil CHANGE image image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE outil ADD CONSTRAINT FK_22627A3EBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE outil ADD CONSTRAINT FK_22627A3E76C50E4A FOREIGN KEY (proprietaire_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE outil ADD CONSTRAINT FK_22627A3EF0840037 FOREIGN KEY (emprunteur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE outil RENAME INDEX idx_dfff4e57bcf5e72d TO IDX_22627A3EBCF5E72D');
        $this->addSql('ALTER TABLE user ADD telephone VARCHAR(20) NOT NULL, ADD adresse VARCHAR(255) NOT NULL, ADD complement_adresse VARCHAR(255) DEFAULT NULL, ADD code_postal VARCHAR(10) NOT NULL, ADD ville VARCHAR(150) NOT NULL, ADD pays VARCHAR(100) NOT NULL, CHANGE roles roles JSON NOT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE historique DROP FOREIGN KEY FK_EDBFD5EC3ED89C80');
        $this->addSql('ALTER TABLE historique DROP FOREIGN KEY FK_EDBFD5ECA76ED395');
        $this->addSql('ALTER TABLE historique CHANGE date_fin date_fin DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE outil DROP FOREIGN KEY FK_22627A3EBCF5E72D');
        $this->addSql('ALTER TABLE outil DROP FOREIGN KEY FK_22627A3E76C50E4A');
        $this->addSql('ALTER TABLE outil DROP FOREIGN KEY FK_22627A3EF0840037');
        $this->addSql('ALTER TABLE outil CHANGE image image VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE outil RENAME INDEX idx_22627a3ebcf5e72d TO IDX_DFFF4E57BCF5E72D');
        $this->addSql('ALTER TABLE user DROP telephone, DROP adresse, DROP complement_adresse, DROP code_postal, DROP ville, DROP pays, CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`');
    }
}
