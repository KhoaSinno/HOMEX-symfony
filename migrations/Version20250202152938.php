<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250202152938 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE schedule_work ADD doctor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE schedule_work ADD CONSTRAINT FK_B30648DB87F4FB17 FOREIGN KEY (doctor_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_B30648DB87F4FB17 ON schedule_work (doctor_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE schedule_work DROP FOREIGN KEY FK_B30648DB87F4FB17');
        $this->addSql('DROP INDEX IDX_B30648DB87F4FB17 ON schedule_work');
        $this->addSql('ALTER TABLE schedule_work DROP doctor_id');
    }
}
