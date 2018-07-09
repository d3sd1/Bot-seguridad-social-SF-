<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180513203600 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        /*
         * Régimen inicial de la SS
         */
        $this->addSql('INSERT INTO contract_accounts (reg,ccc,name) VALUES (0111,28149794464,"WORKOUT"),(0111,28223561449,"WORKOUT_RETAIL")');
    }

    public function down(Schema $schema): void
    {

    }
}
