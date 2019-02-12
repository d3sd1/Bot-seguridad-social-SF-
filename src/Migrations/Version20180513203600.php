<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
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
        $this->addSql('INSERT INTO contract_coefficient (coefficient,description) VALUES 
                            (500,"4 Horas"),
                            (625,"5 Horas"),
                            (750,"6 Horas"),
                            (402,"7+ Horas (Tiempo completo)")');
        $this->addSql('INSERT INTO contract_type (id,TYPE) VALUES (1,"INDEFINIDO"),(2,"DURACION_DETERMINADA"),(3,"TEMPORAL")');
        $this->addSql('INSERT INTO contract_time_type (id,time_type) VALUES (1,"TIEMPO_COMPLETO"),(2,"TIEMPO_PARCIAL"),(3,"FIJO_DISCONTINUO")');
        $this->addSql('INSERT INTO contract_key (ckey,TYPE,time_type,description) VALUES 
                            (100,1,1,"ORDINARIO"),
                            (109,1,1,"FOMENTO CONTRATACIÓN INDEFINIDA TRANSFORMACIÓN CONTRATO TEMPORAL"),
                            (130,1,1,"PERSONAS CON DISCAPACIDAD"),
                            (139,1,1,"PERSONAS CON DISCAPACIDAD TRANSFORMACIÓN CONTRATO TEMPORAL"),
                            (150,1,1,"FOMENTO CONTRATACIÓN INDEFINIDA INICIAL"),
                            (189,1,1,"TRANSFORMACIÓN CONTRATO TEMPORAL"),
                            (200,1,2,"ORDINARIO"),
                            (209,1,2,"FOMENTO CONTRATACIÓN INDEFINIDA TRANSFORMACIÓN CONTRATO TEMPORAL"),
                            (230,1,2,"PERSONAS CON DISCAPACIDAD"),
                            (239,1,2,"PERSONAS CON DISCAPACIDAD TRANSFORMACIÓN CONTRATO TEMPORAL"),
                            (250,1,2,"FOMENTO CONTRATACIÓN INDEFINIDA INICIAL"),
                            (289,1,2,"TRANSFORMACIÓN CONTRATO TEMPORAL"),
                            (300,1,3,""),
                            (309,1,3,"FOMENTO CONTRATACIÓN INDEFINIDA TRANSFORMACIÓN CONTRATO TEMPORAL"),
                            (330,1,3,"PERSONAS CON DISCAPACIDAD"),
                            (339,1,2,"PERSONAS CON DISCAPACIDAD TRANSFORMACIÓN CONTRATO TEMPORAL"),
                            (350,1,3,"FOMENTO CONTRATACIÓN INDEFINIDA INICIAL"),
                            (389,1,3,"TRANSFORMACIÓN CONTRATO TEMPORAL"),
                            (401,2,1,"OBRA O SERVICIO DETERMINADO"),
                            (402,2,1,"EVENTUAL CIRCUNSTANCIAS DE LA PRODUCCIÓN"),
                            (408,3,1,"CARÁCTER ADMINISTRATIVO"),
                            (410,2,1,"INTERINIDAD"),
                            (418,2,1,"INTERINIDAD CARÁCTER ADMINISTRATIVO"),
                            (420,3,1,"PRÁCTICAS"),
                            (421,3,1,"FORMACIÓN Y APRENDIZAJE"),
                            (430,3,1,"PERSONAS CON DISCAPACIDAD"),
                            (441,3,1,"RELEVO"),
                            (450,3,1,"FOMENTO CONTRATACIÓN INDEFINIDA"),
                            (452,3,1,"EMPRESAS DE INSERCIÓN"),
                            (501,2,2,"OBRA O SERVICIO DETERMINADO"),
                            (502,2,2,"EVENTUAL CIRCUNSTANCIAS DE LA PRODUCCIÓN"),
                            (508,3,2,"CARÁCTER ADMINISTRATIVO"),
                            (510,2,2,"INTERINIDAD"),
                            (518,2,2,"INTERINIDAD CARÁCTER ADMINISTRATIVO"),
                            (520,3,2,"PRÁCTICAS"),
                            (530,3,2,"PERSONAS CON DISCAPACIDAD"),
                            (540,3,2,"JUBILADO PARCIAL"),
                            (541,3,2,"RELEVO"),
                            (550,3,2,"FOMENTO CONTRATACIÓN INDEFINIDA"),
                            (552,3,2,"EMPRESAS DE INSERCIÓN")');
        $this->addSql('INSERT INTO server_status_options (id,STATUS) VALUES (1,"RUNNING"),(3,"CRASHED"),(4,"CRASHED_RELOADING"),(5,"RUNNING_WITH_WARNINGS"),(2,"OFFLINE"),(6,"BOOTING"),(7,"WAITING_TASKS"),(8,"SS_PAGE_DOWN")');
        $this->addSql('INSERT INTO server_status (current_status_id) VALUES (2)');
        $this->addSql('INSERT INTO log_type (id,type) VALUES (1,"ERROR"), (2,"WARNING"), (3,"INFO"), (4,"SUCCESS")');
        $this->addSql('INSERT INTO process_status (id,status) VALUES (1,"COMPLETED"), (2,"IN_PROCESS"), (3,"STOPPED"), (4,"AWAITING"), (5,"ERROR"), (6,"REMOVED"), (9,"ABORTED"),(10, "TIMED_OUT")');
        $this->addSql('INSERT INTO process_type (TYPE) VALUES
                            ("ALTA"),("BAJA"),("ANULACION_ALTA_PREVIA"),("ANULACION_ALTA_CONSOLIDADA"),
                            ("ANULACION_BAJA_PREVIA"), ("ANULACION_BAJA_CONSOLIDADA"), ("CAMBIO_CONTRATO_CONSOLIDADO"), ("CAMBIO_CONTRATO_PREVIO"),("CONSULTA_IPF"),
                            ("CONSULTA_NAF"),("CONSULTA_ALTAS_CCC"),("CONSULTA_TA"),("CONSULTA_ALTA")');
        $this->addSql('INSERT INTO contract_accounts (reg,ccc,name) VALUES (0111,28149794464,"WORKOUT"),(0111,28223561449,"WORKOUT_RETAIL")');
    }

    public function down(Schema $schema): void
    {

    }
}
