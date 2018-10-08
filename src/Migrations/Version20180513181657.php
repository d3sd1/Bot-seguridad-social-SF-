<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180513181657 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        /*
         * Migración de contratos: Agregada base de datos con los tipos de contrato y las modalidades.
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
    }

    public function down(Schema $schema): void
    {

    }
}
