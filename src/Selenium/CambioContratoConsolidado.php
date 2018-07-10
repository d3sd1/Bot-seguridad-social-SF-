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

class CambioContratoConsolidado extends Operation
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
        $this->driver->findElement(WebDriverBy::name('txt_SDFPROAFI'))->sendKeys(substr($this->operation->getNaf(), 0, 2));
        $this->driver->findElement(WebDriverBy::name('txt_SDFCODAFI'))->sendKeys(substr($this->operation->getNaf(), 2, 10));

        /*
        * Rellenar identificación de personas físicas
         * Primer campo, un dígito INT
         * Segundo campo, diez dígitos INT
        */
        $this->driver->findElement(WebDriverBy::name('txt_SDFTIPPFI_ayuda'))->sendKeys($this->operation->getIpt());
        $this->driver->findElement(WebDriverBy::name('txt_SDFNUMPFI'))->sendKeys($this->operation->getIpf());

        /*
         * Rellenar régimen
         * Un sólo campo de 4 dígitos INT
        */
        $this->driver->findElement(WebDriverBy::name('txt_SDFREGAFI_ayuda'))->sendKeys($this->operation->getCca()->getReg());

        /*
        * Rellenar cuenta de cotización
        * Primer campo, dos dígitos INT
        * Segundo campo, nueve dígitos INT
        */
        $this->driver->findElement(WebDriverBy::name('txt_SDFTESCTACOT'))->sendKeys(substr($this->operation->getCca()->getCcc(), 0, 2));
        $this->driver->findElement(WebDriverBy::name('txt_SDFCTACOT'))->sendKeys(substr($this->operation->getCca()->getCcc(), 2, 9));

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
        $this->waitFormSubmit(WebDriverBy::name('txt_SDFFREALDD'));

        /*
         * Revisar si hay errores en el formulario. Si los hay, detener ejecución.
         */
        if ($this->hasFormErrors()) {
            return false;
        }
        /*
         * **************************************
         * Rellenar segunda parte del formulario.
         * **************************************
         */

        /*
         * Rellenar fecha real del alta.
         * Tres campos (día, mes, año)
         */
        $this->driver->findElement(WebDriverBy::name('txt_SDFFREALDD'))->sendKeys($this->operation->getFrc()->format("d"));
        $this->driver->findElement(WebDriverBy::name('txt_SDFFREALMM'))->sendKeys($this->operation->getFrc()->format("m"));
        $this->driver->findElement(WebDriverBy::name('txt_SDFFREALAA'))->sendKeys($this->operation->getFrc()->format("Y"));

        /*
         * Rellenar tipo de contrato.
         * Tres dígitos INT.
         */
        $this->driver->findElement(WebDriverBy::name('txt_SDFTICO_ayuda'))->clear()->sendKeys($this->operation->getTco()->getCkey());

        /*
         * Rellenar coeficiente de tiempo parcial,
         * sólo si el contrato es de tiempo parcial.
         * Tres dígitos INT.
         */

        $this->driver->findElement(WebDriverBy::name('txt_SDFCOEFCO_ayuda'))->clear();
        $timeType = $this->em->getRepository("App:ContractTimeType")->findOneBy(['id' => $this->operation->getTco()->getTimeType()])->getTimeType();
        if ($timeType === "TIEMPO_PARCIAL") {
            $this->driver->findElement(WebDriverBy::name('txt_SDFCOEFCO_ayuda'))->sendKeys($this->operation->getCoe()->getCoefficient());
        }

        /*
         * Enviar formulario.
         */
        $this->takeScreenShoot();
        $this->driver->findElement(WebDriverBy::name('btn_Sub2207401004'))->click();

        /*
         * Esperar a que se envíe el formulario.
         */
        $this->waitFormSubmit(WebDriverBy::id('SDFSITAFI_ayuda'));

        /*
         * Revisar si hay errores en el formulario. Si los hay, detener ejecución.
         */
        if ($this->hasFormErrors()) {
            return false;
        }
        return true;
    }
}