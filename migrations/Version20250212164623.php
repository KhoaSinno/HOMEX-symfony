<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250212164623 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment ADD patient_fullname VARCHAR(255) DEFAULT NULL, ADD patient_date_of_birth DATE DEFAULT NULL, ADD patient_phone_number VARCHAR(255) DEFAULT NULL, ADD patient_address VARCHAR(255) DEFAULT NULL, ADD patient_gender VARCHAR(255) DEFAULT NULL, ADD patient_email VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment DROP patient_fullname, DROP patient_date_of_birth, DROP patient_phone_number, DROP patient_address, DROP patient_gender, DROP patient_email');
    }
}
