<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250404213036 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE card (id INT AUTO_INCREMENT NOT NULL, pan VARCHAR(16) NOT NULL, exp_date VARCHAR(5) NOT NULL, cvv VARCHAR(3) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', except_sca VARCHAR(255) DEFAULT NULL, card_psd2 VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE emv3_ds (id INT AUTO_INCREMENT NOT NULL, protocol_version VARCHAR(255) NOT NULL, three_dserver_trans_id VARCHAR(255) NOT NULL, three_dsinfo VARCHAR(255) NOT NULL, three_dsmethod_url VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', three_dscomp_ind VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transaction (id INT AUTO_INCREMENT NOT NULL, emv3_ds_id INT DEFAULT NULL, card_id INT DEFAULT NULL, trans_order VARCHAR(255) NOT NULL, trans_type VARCHAR(255) NOT NULL, currency VARCHAR(255) NOT NULL, amount VARCHAR(255) NOT NULL, is_paid TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_723705D1FB3C9E96 (emv3_ds_id), INDEX IDX_723705D14ACC9A20 (card_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1FB3C9E96 FOREIGN KEY (emv3_ds_id) REFERENCES emv3_ds (id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D14ACC9A20 FOREIGN KEY (card_id) REFERENCES card (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1FB3C9E96');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D14ACC9A20');
        $this->addSql('DROP TABLE card');
        $this->addSql('DROP TABLE emv3_ds');
        $this->addSql('DROP TABLE transaction');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
