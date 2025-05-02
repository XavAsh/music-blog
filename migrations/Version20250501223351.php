<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250501223100 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Drop existing table first
        $this->addSql('DROP TABLE IF EXISTS comment');
        
        // Create table with correct structure
        $this->addSql('CREATE TABLE comment (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT DEFAULT NULL,
            article_id INT DEFAULT NULL,
            content VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_9474526CA76ED395 (user_id),
            INDEX IDX_9474526C7294869C (article_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Add foreign key constraints
        $this->addSql('ALTER TABLE comment 
            ADD CONSTRAINT FK_9474526CA76ED395 
            FOREIGN KEY (user_id) REFERENCES user (id)');
            
        $this->addSql('ALTER TABLE comment 
            ADD CONSTRAINT FK_9474526C7294869C 
            FOREIGN KEY (article_id) REFERENCES article (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS comment');
    }
}