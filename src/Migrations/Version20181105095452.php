<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181105095452 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE users_cards (user_id INT NOT NULL, card_id INT NOT NULL, INDEX IDX_E3B5FCB4A76ED395 (user_id), INDEX IDX_E3B5FCB44ACC9A20 (card_id), PRIMARY KEY(user_id, card_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users_decks (user_id INT NOT NULL, deck_id INT NOT NULL, INDEX IDX_448B627BA76ED395 (user_id), INDEX IDX_448B627B111948DC (deck_id), PRIMARY KEY(user_id, deck_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wishlist (user_id INT NOT NULL, card_wanted_id INT NOT NULL, INDEX IDX_9CE12A31A76ED395 (user_id), INDEX IDX_9CE12A312D2E05E7 (card_wanted_id), PRIMARY KEY(user_id, card_wanted_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE users_cards ADD CONSTRAINT FK_E3B5FCB4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE users_cards ADD CONSTRAINT FK_E3B5FCB44ACC9A20 FOREIGN KEY (card_id) REFERENCES card (id)');
        $this->addSql('ALTER TABLE users_decks ADD CONSTRAINT FK_448B627BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE users_decks ADD CONSTRAINT FK_448B627B111948DC FOREIGN KEY (deck_id) REFERENCES deck (id)');
        $this->addSql('ALTER TABLE wishlist ADD CONSTRAINT FK_9CE12A31A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE wishlist ADD CONSTRAINT FK_9CE12A312D2E05E7 FOREIGN KEY (card_wanted_id) REFERENCES card (id)');
        $this->addSql('ALTER TABLE user ADD facebook_id VARCHAR(255) DEFAULT NULL, ADD google_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE users_cards');
        $this->addSql('DROP TABLE users_decks');
        $this->addSql('DROP TABLE wishlist');
        $this->addSql('ALTER TABLE user DROP facebook_id, DROP google_id');
    }
}
