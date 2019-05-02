<?php

namespace App\Selenium;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use App\Constants\ProdUrlConstants;

/*
 * NOTA IMPORTANTE:
 * En el modo DEV los formularios
 * NO SE ENVÍAN!
 * SIEMPRE VA A SALIR EL ERROR DE AFILIADO INEXISTENTE.
 */

class ConsultaAltasCcc extends Operation
{

    public function doOperation()
    {

        /*
         * Rellenar régimen
         * Un sólo campo de 4 dígitos INT
        */
        $this->driver->findElement(WebDriverBy::name('txt_SDFREGCTA_ayuda'))->sendKeys($this->operation->getCca()->getReg());

        /*
        * Rellenar cuenta de cotización
        * Primer campo, dos dígitos INT
        * Segundo campo, nueve dígitos INT
        */
        $this->driver->findElement(WebDriverBy::name('txt_SDFTESCTA'))->sendKeys(substr($this->operation->getCca()->getCcc(), 0, 2));
        $this->driver->findElement(WebDriverBy::name('txt_SDFNUMCTA'))->sendKeys(substr($this->operation->getCca()->getCcc(), 2, 9));

        /*
        * Clickar en el botón de enviar
        * Aquí concluye la primera parte del formulario
        */
        $this->takeScreenShoot();
        $this->driver->findElement(WebDriverBy::name('btn_Sub2207601004'))->click();

        $this->container->get("app.dblogger")->info("Enviando primer formulario...");

        /*
         * Esperar a que se envíe el formulario.
         */
        $this->waitFormSubmit(WebDriverBy::name('chk_SDFCONSSALDOS'));

        /*
         * Marcar todas las opciones para que salgan mas cosas en el formulario.
         */
        $this->driver->findElement(WebDriverBy::name('chk_SDFCONSSALDOS'))->click();
        $this->driver->findElement(WebDriverBy::name('chk_SDFCONSCOLE'))->click();
        $this->driver->findElement(WebDriverBy::name('chk_SDFCONSSII'))->click();


        /*
        * Clickar en el botón de enviar
        * Aquí concluye la segunda parte del formulario
        */
        $this->takeScreenShoot();
        $this->driver->findElement(WebDriverBy::name('btn_Sub2207601004'))->click();

        $this->container->get("app.dblogger")->info("Enviando segundo formulario...");

        /*
         * Esperar a que se envíe el formulario.
         */
        $this->waitFormSubmit(WebDriverBy::name('chk_SDFCONSALTA'));

        /*
         * Marcar todas las opciones para que salgan mas cosas en el formulario.
         */
        $this->driver->findElement(WebDriverBy::name('chk_SDFCONSALTA'))->click();
        $this->driver->findElement(WebDriverBy::name('chk_SDFCONSAC'))->click();
        $this->driver->findElement(WebDriverBy::name('chk_SDFCONSPREV'))->click();
        $this->driver->findElement(WebDriverBy::name('chk_SDFCONSHUELGA'))->click();
        $this->driver->findElement(WebDriverBy::name('chk_SDFCONSASIMI'))->click();
        $this->driver->findElement(WebDriverBy::name('chk_SDFCONSSUBCONOTRO'))->click();
        $this->driver->findElement(WebDriverBy::name('chk_SDFCONSSUBCON'))->click();
        $this->driver->findElement(WebDriverBy::name('chk_SDFCONSEMPRE'))->click();
        $this->driver->findElement(WebDriverBy::name('chk_SDFCONSATEP'))->click();
        $this->driver->findElement(WebDriverBy::name('chk_SDFCONSCONVCOL'))->click();
        $this->driver->findElement(WebDriverBy::name('chk_SDFCONSFUNCI'))->click();


        /*
        * Clickar en el botón de enviar
        * Aquí concluye la tercera parte del formulario
        */
        $this->takeScreenShoot();
        $this->driver->findElement(WebDriverBy::name('btn_Sub2207601004'))->click();

        $this->container->get("app.dblogger")->info("Enviando tercer formulario...");

        /*
         * Esperar a que se envíe el formulario.
         */
        $this->waitFormSubmit(WebDriverBy::id('SDFNISS3'));


        /*
         * Revisar si hay errores en el formulario. Si los hay, detener ejecución.
         */
        if ($this->hasFormErrors(true, true)) {
            return false;
        }

        /*
         * Empezar iteraciones para recoger datos.
         */

        $workers = [];
        $baseElname = "Sub0900113079_{{COL}}_{{ROW}}";
        $workersRemaining = true;
        $actualPage = 1;
        while ($workersRemaining) {
            for ($row = 0; $row < 13; $row++) {
                try {
                    $worker = new \stdClass();
                    $worker->NAF = $this->driver->findElement(WebDriverBy::id(str_replace(['{{ROW}}', '{{COL}}'], [$row, 1], $baseElname)))->getText();
                    $worker->SIT = $this->driver->findElement(WebDriverBy::id(str_replace(['{{ROW}}', '{{COL}}'], [$row, 2], $baseElname)))->getText();
                    $worker->NAP = $this->driver->findElement(WebDriverBy::id(str_replace(['{{ROW}}', '{{COL}}'], [$row, 3], $baseElname)))->getText();
                    $worker->IFT = $this->driver->findElement(WebDriverBy::id(str_replace(['{{ROW}}', '{{COL}}'], [$row, 4], $baseElname)))->getText();
                    array_push($workers, $worker);
                } catch (\Exception $e) {
                    $this->container->get("app.dblogger")->info("Deteniendo recogida de datos. No hay más usuarios.");
                    $workersRemaining = false;
                    break;
                }
            }
            $this->takeScreenShoot();
            $this->driver->findElement(WebDriverBy::name('btn_Sub2207901001'))->click();

            $this->container->get("app.dblogger")->info("Cargando más registros...");

            /*
             * Esperar a que se envíe el formulario.
             */
            $this->waitFormSubmit(WebDriverBy::id('Sub0900113079'));
            $this->container->get("app.dblogger")->info("Procesada página " . $actualPage . ", intentando avanzar...");
            if ($this->hasFormErrors(false, true)) {
                $this->container->get("app.dblogger")->info("Deteniendo recogida de datos. No hay más paginación.");
                $workersRemaining = false;
                break;
            }
            $actualPage++;
        }

        /*
         * Guardar su trabajadores en la base de datos.
         */
        $dataDir = "/var/www/data";
        $destinationFolder = $dataDir . "/" . $this->operationName;
        if (!file_exists($destinationFolder)) {
            mkdir($destinationFolder);
        }
        $fileName = $destinationFolder . "/" . $this->operation->getId() . ".db";
        $fp = fopen($fileName, 'w');
        fwrite($fp, json_encode($workers));
        fclose($fp);

        $this->updateConsultaData(
            $fileName
        );

        return true;
    }
}