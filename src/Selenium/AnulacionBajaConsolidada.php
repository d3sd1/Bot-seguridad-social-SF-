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

class AnulacionBajaConsolidada extends Operation
{

    public function doOperation()
    {
        $this->container->get("app.dblogger")->info("Rellenando primer formulario...");
        /*
         * Rellenar número de afiliación
         * Primer campo, dos dígitos INT
         * Segundo campo, diez dígitos INT
        */
        $this->driver->findElement(WebDriverBy::name('txt_SDFIDPRONAF'))->sendKeys(substr($this->operation->getNaf(), 0, 2));
        $this->driver->findElement(WebDriverBy::name('txt_SDFIDNAFCON'))->sendKeys(substr($this->operation->getNaf(), 2, 10));


        /*
        * Rellenar identificación de personas físicas
         * Primer campo, un dígito INT
         * Segundo campo, diez dígitos INT
        */
        $this->driver->findElement(WebDriverBy::name('txt_SDFIDTIPOPF_ayuda'))->sendKeys($this->operation->getIpt());
        $this->driver->findElement(WebDriverBy::name('txt_SDFIDNIDEPF'))->sendKeys($this->operation->getIpf());

        /*
         * Rellenar régimen
         * Un sólo campo de 4 dígitos INT
        */
        $this->driver->findElement(WebDriverBy::name('txt_SDFIDREGIMEN_ayuda'))->sendKeys($this->operation->getCca()->getReg());

        /*
        * Rellenar cuenta de cotización
        * Primer campo, dos dígitos INT
        * Segundo campo, nueve dígitos INT
        */
        $this->driver->findElement(WebDriverBy::name('txt_SDFIDTESCTA'))->sendKeys(substr($this->operation->getCca()->getCcc(), 0, 2));
        $this->driver->findElement(WebDriverBy::name('txt_SDFIDCTACON'))->sendKeys(substr($this->operation->getCca()->getCcc(), 2, 9));

        /*
         * Rellenar fecha real de la baja.
         * Tres campos (día, mes, año)
         */
        $this->driver->findElement(WebDriverBy::name('txt_SDFIDFREALDD'))->sendKeys($this->operation->getFrb()->format("d"));
        $this->driver->findElement(WebDriverBy::name('txt_SDFIDFREALMM'))->sendKeys($this->operation->getFrb()->format("m"));
        $this->driver->findElement(WebDriverBy::name('txt_SDFIDFREALAA'))->sendKeys($this->operation->getFrb()->format("Y"));

        /*
         * Enviar formulario.
         */
        $this->takeScreenShoot();
        $this->driver->findElement(WebDriverBy::name('btn_Sub2207401004'))->click();

        /*
         * Esperar a que se envíe el formulario.
         */
        $this->waitFormSubmit(WebDriverBy::id('S2_0603901009'));

        /*
         * Revisar si hay errores en el formulario. Si los hay, detener ejecución.
         */
        if ($this->hasFormErrors()) {
            return false;
        }

        $this->container->get("app.dblogger")->info("Enviando segundo formulario...");

        /*
         * Enviar segundo formulario.
         */
        $this->takeScreenShoot();
        $this->driver->findElement(WebDriverBy::name('btn_Sub2205301005'))->click();

        /*
         * Esperar a que se envíe el formulario.
         */
        $this->waitFormSubmit(WebDriverBy::name('txt_SDFSITALT_ayuda'));

        /*
         * Revisar si hay errores en el formulario. Si los hay, detener ejecución.
         */
        if ($this->hasFormErrors()) {
            return false;
        }

        return true;
    }
}