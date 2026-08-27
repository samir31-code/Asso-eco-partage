<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260717124225 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE photo (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, outil_id INT NOT NULL, INDEX IDX_14B784183ED89C80 (outil_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B784183ED89C80 FOREIGN KEY (outil_id) REFERENCES outil (id)');
        $this->addSql('ALTER TABLE historique CHANGE date_fin date_fin DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE historique ADD CONSTRAINT FK_EDBFD5EC3ED89C80 FOREIGN KEY (outil_id) REFERENCES outil (id)');
        $this->addSql('ALTER TABLE historique ADD CONSTRAINT FK_EDBFD5ECA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE outil CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE caracteristiques caracteristiques JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE outil ADD CONSTRAINT FK_22627A3EBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE outil ADD CONSTRAINT FK_22627A3E76C50E4A FOREIGN KEY (proprietaire_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE outil ADD CONSTRAINT FK_22627A3EF0840037 FOREIGN KEY (emprunteur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL, CHANGE complement_adresse complement_adresse VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B784183ED89C80');
        $this->addSql('DROP TABLE photo');
        $this->addSql('ALTER TABLE historique DROP FOREIGN KEY FK_EDBFD5EC3ED89C80');
        $this->addSql('ALTER TABLE historique DROP FOREIGN KEY FK_EDBFD5ECA76ED395');
        $this->addSql('ALTER TABLE historique CHANGE date_fin date_fin DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE outil DROP FOREIGN KEY FK_22627A3EBCF5E72D');
        $this->addSql('ALTER TABLE outil DROP FOREIGN KEY FK_22627A3E76C50E4A');
        $this->addSql('ALTER TABLE outil DROP FOREIGN KEY FK_22627A3EF0840037');
        $this->addSql('ALTER TABLE outil CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE caracteristiques caracteristiques LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE user CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`, CHANGE complement_adresse complement_adresse VARCHAR(255) DEFAULT \'NULL\'');
    }
}
