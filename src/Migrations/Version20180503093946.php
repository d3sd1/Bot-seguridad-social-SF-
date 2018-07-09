<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180503093946 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        /*
         * Migración base: Cola de trabajos, estado del servidor.
         */
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('INSERT INTO server_status_options (id,STATUS) VALUES (1,"RUNNING"),(3,"CRASHED"),(4,"CRASHED_RELOADING"),(5,"RUNNING_WITH_WARNINGS"),(2,"OFFLINE"),(6,"BOOTING"),(7,"WAITING_TASKS"),(8,"SS_PAGE_DOWN")');
        $this->addSql('INSERT INTO server_status (current_status_id) VALUES (2)');
        $this->addSql('INSERT INTO log_type (id,type) VALUES (1,"ERROR"), (2,"WARNING"), (3,"INFO"), (4,"SUCCESS")');
        $this->addSql('INSERT INTO process_status (id,status) VALUES (1,"COMPLETED"), (2,"IN_PROCESS"), (3,"STOPPED"), (4,"AWAITING"), (5,"ERROR"), (6,"REMOVED")');
        $this->addSql('INSERT INTO process_type (TYPE) VALUES
                            ("ALTA"),("BAJA"),("ANULACION_ALTA_PREVIA"),("ANULACION_ALTA_CONSOLIDADA"),
                            ("ANULACION_BAJA_PREVIA"), ("ANULACION_BAJA_CONSOLIDADA"), ("CAMBIO_CONTRATO_CONSOLIDADO"), ("CAMBIO_CONTRATO_PREVIO"),("CONSULTA_IPF"),
                            ("CONSULTA_NAF"),("CONSULTA_ALTAS_CCC"),("CONSULTA_TA")');
    }

    public function down(Schema $schema)
    {

    }
}
