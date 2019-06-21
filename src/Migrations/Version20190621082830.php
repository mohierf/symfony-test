<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190621082830 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE host (id INT AUTO_INCREMENT NOT NULL, check_command_id INT DEFAULT NULL, host_name VARCHAR(255) NOT NULL, display_name VARCHAR(255) NOT NULL, register TINYINT(1) NOT NULL, address VARCHAR(255) NOT NULL, check_interval INT NOT NULL, INDEX IDX_CF2713FDA82F4D98 (check_command_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE command (id INT AUTO_INCREMENT NOT NULL, command_name VARCHAR(255) NOT NULL, command_line VARCHAR(1024) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE host ADD CONSTRAINT FK_CF2713FDA82F4D98 FOREIGN KEY (check_command_id) REFERENCES command (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE host DROP FOREIGN KEY FK_CF2713FDA82F4D98');
        $this->addSql('DROP TABLE host');
        $this->addSql('DROP TABLE command');
    }
}
