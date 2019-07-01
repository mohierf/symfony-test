<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190630072035 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE host CHANGE check_command_id check_command_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE realm CHANGE parent_id parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE json_field CHANGE parent_id parent_id INT DEFAULT NULL, CHANGE format format VARCHAR(255) DEFAULT NULL, CHANGE pattern pattern VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE json_schema CHANGE content content JSON NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE user');
        $this->addSql('ALTER TABLE host CHANGE check_command_id check_command_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE json_field CHANGE parent_id parent_id INT DEFAULT NULL, CHANGE format format VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE pattern pattern VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE json_schema CHANGE content content LONGTEXT NOT NULL COLLATE utf8mb4_bin');
        $this->addSql('ALTER TABLE realm CHANGE parent_id parent_id INT DEFAULT NULL');
    }
}
