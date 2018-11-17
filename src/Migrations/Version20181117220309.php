<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181117220309 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE card ADD name VARCHAR(255) NOT NULL, ADD card_set VARCHAR(255) DEFAULT NULL, ADD type VARCHAR(255) DEFAULT NULL, ADD faction VARCHAR(255) DEFAULT NULL, ADD rarity VARCHAR(255) DEFAULT NULL, ADD text VARCHAR(500) DEFAULT NULL, ADD flavor VARCHAR(500) DEFAULT NULL, ADD img VARCHAR(255) DEFAULT NULL, ADD img_gold VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE card DROP name, DROP card_set, DROP type, DROP faction, DROP rarity, DROP text, DROP flavor, DROP img, DROP img_gold');
    }
}
