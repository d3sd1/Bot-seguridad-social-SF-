<?php

namespace App\Selenium;

use Doctrine\ORM\EntityManager;
use Facebook\WebDriver\WebDriverSelect;
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

class ConsultaAlta extends Operation
{

    public function doOperation()
    {
        $this->container->get("app.dblogger")->info("Rellenando primer formulario...");
        /*
         * **************************************
         * Rellenar primera parte del formulario.
         * **************************************
         */

        /*
         * Rellenar número de afiliación
         * Primer campo, dos dígitos INT
         * Segundo campo, diez dígitos INT
        */
        $this->driver->findElement(WebDriverBy::name('txt_SDFTESNAF'))->sendKeys(substr($this->operation->getNaf(), 0, 2));
        $this->driver->findElement(WebDriverBy::name('txt_SDFNAF'))->sendKeys(substr($this->operation->getNaf(), 2, 10));

        /*
         * Rellenar régimen
         * Un sólo campo de 4 dígitos INT
        */
        $this->driver->findElement(WebDriverBy::name('txt_SDFREGCTA_NH'))->sendKeys($this->operation->getCca()->getReg());

        /*
        * Rellenar cuenta de cotización
        * Primer campo, dos dígitos INT
        * Segundo campo, nueve dígitos INT
        */
        $this->driver->findElement(WebDriverBy::name('txt_SDFTESCTA'))->sendKeys(substr($this->operation->getCca()->getCcc(), 0, 2));
        $this->driver->findElement(WebDriverBy::name('txt_SDFCUENTA'))->sendKeys(substr($this->operation->getCca()->getCcc(), 2, 9));

        /*
         * Rellenar fecha real de la consulta.
         * Tres campos (día, mes, año)
         */
        $this->driver->findElement(WebDriverBy::name('txt_SDFDIA'))->sendKeys($this->operation->getFrc()->format("d"));
        $this->driver->findElement(WebDriverBy::name('txt_SDFMES'))->sendKeys($this->operation->getFrc()->format("m"));
        $this->driver->findElement(WebDriverBy::name('txt_SDFAO'))->sendKeys($this->operation->getFrc()->format("Y"));

        /*
         * Rellenar siempre en opción online.
         */
        $selectBaja = new WebDriverSelect($this->driver->findElement(WebDriverBy::name('cbo_ListaTipoImpresion')));
        $selectBaja->selectByVisibleText('OnLine');

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
        $this->waitFormSubmit(WebDriverBy::id('Sub0900112079'));

        /*
         * Revisar si hay errores en el formulario. Si los hay, detener ejecución.
         */
        if ($this->hasFormErrors()) {
            return false;
        }

        /*
         * Limpiar archivos temporales antes de continuar.
         */
        $this->clearTmpFolder();

        /*
         * Doble click para descargar el TA, sobre el primer elemento de la tabla (más próximo a la fecha indicada).
         * Esperar a que cargue antes, ya que es asíncrono.
         */
        if (!$this->waitFormSubmit(WebDriverBy::id('Sub0900112079_1_0'))) {
            $this->container->get("app.dblogger")->info("No se encontró la tabla.");
            return false;
        }
        $this->container->get("app.dblogger")->info("Clickando elemento...");


        $op = new \stdClass();
        $op->alta = new \stdClass();
        $op->baja = new \stdClass();

        $fil = 0;
        while(true) {
            $elKeyname = "Sub0900112079_{{col}}_{{fil}}";
            if(count($this->driver->findElements(WebDriverBy::id(str_replace(array("{{col}}","{{fil}}"),array("1",$fil),$elKeyname)))) === 0) {
                break;
            }
            $elSsName = $this->driver->findElement(WebDriverBy::id(str_replace(array("{{col}}","{{fil}}"),array("1",$fil),$elKeyname)))->getText();
            if(stristr($elSsName, 'alta') !== false) {
                $op->alta->nombreSS = $elSsName;
                $op->alta->fecha = $this->driver->findElement(WebDriverBy::id(str_replace(array("{{col}}","{{fil}}"),array("2",$fil),$elKeyname)))->getText();
            }
            else if(stristr($elSsName, 'baja') !== false) {
                $op->alta->nombreSS = $elSsName;
                $op->alta->fecha = $this->driver->findElement(WebDriverBy::id(str_replace(array("{{col}}","{{fil}}"),array("2",$fil),$elKeyname)))->getText();
            }

            $fil++;
        }
        /*
         * Guardar en la DB.
         */
        $this->updateConsultaData(
            json_encode($op)
        );

        return true;
    }
}