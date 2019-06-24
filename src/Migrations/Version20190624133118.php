<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190624133118 extends AbstractMigration
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
        $this->addSql('CREATE TABLE realm (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, realm_name VARCHAR(255) NOT NULL, alias VARCHAR(255) NOT NULL, INDEX IDX_FA96DBDA727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE json_field (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, json_schema_id INT UNSIGNED NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, format VARCHAR(255) DEFAULT NULL, INDEX IDX_5E2C8E17727ACA70 (parent_id), INDEX IDX_5E2C8E1794065426 (json_schema_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE json_schema (id INT UNSIGNED AUTO_INCREMENT NOT NULL, content JSON NOT NULL, name VARCHAR(255) NOT NULL, creation DATETIME NOT NULL, last_update DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE host ADD CONSTRAINT FK_CF2713FDA82F4D98 FOREIGN KEY (check_command_id) REFERENCES command (id)');
        $this->addSql('ALTER TABLE realm ADD CONSTRAINT FK_FA96DBDA727ACA70 FOREIGN KEY (parent_id) REFERENCES realm (id)');
        $this->addSql('ALTER TABLE json_field ADD CONSTRAINT FK_5E2C8E17727ACA70 FOREIGN KEY (parent_id) REFERENCES json_field (id)');
        $this->addSql('ALTER TABLE json_field ADD CONSTRAINT FK_5E2C8E1794065426 FOREIGN KEY (json_schema_id) REFERENCES json_schema (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE host DROP FOREIGN KEY FK_CF2713FDA82F4D98');
        $this->addSql('ALTER TABLE realm DROP FOREIGN KEY FK_FA96DBDA727ACA70');
        $this->addSql('ALTER TABLE json_field DROP FOREIGN KEY FK_5E2C8E17727ACA70');
        $this->addSql('ALTER TABLE json_field DROP FOREIGN KEY FK_5E2C8E1794065426');
        $this->addSql('DROP TABLE host');
        $this->addSql('DROP TABLE command');
        $this->addSql('DROP TABLE realm');
        $this->addSql('DROP TABLE json_field');
        $this->addSql('DROP TABLE json_schema');
    }
}
