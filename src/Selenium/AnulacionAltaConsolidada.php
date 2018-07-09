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

class AnulacionAltaConsolidada extends Operation
{

    public function doOperation()
    {
        $this->container->get("app.dblogger")->info("Rellenando primer formulario...");
        /*
         * Rellenar número de afiliación
         * Primer campo, dos dígitos INT
         * Segundo campo, diez dígitos INT
        */
        $this->driver->findElement(WebDriverBy::name('txt_SDFTESORNAF'))->sendKeys(substr($this->operation->getNaf(), 0, 2));
        $this->driver->findElement(WebDriverBy::name('txt_SDFNUMNAF'))->sendKeys(substr($this->operation->getNaf(), 2, 10));

        /*
         * Rellenar régimen
         * Un sólo campo de 4 dígitos INT
        */
        $this->driver->findElement(WebDriverBy::name('txt_SDFREGCC_ayuda'))->sendKeys($this->operation->getCca()->getReg());

        /*
        * Rellenar cuenta de cotización
        * Primer campo, dos dígitos INT
        * Segundo campo, nueve dígitos INT
        */
        $this->driver->findElement(WebDriverBy::name('txt_SDFTESCC'))->sendKeys(substr($this->operation->getCca()->getCcc(), 0, 2));
        $this->driver->findElement(WebDriverBy::name('txt_SDFNUMCC'))->sendKeys(substr($this->operation->getCca()->getCcc(), 2, 9));

        /*
         * Enviar formulario.
         */
        $this->takeScreenShoot();
        $this->driver->findElement(WebDriverBy::name('btn_Sub2207501004'))->click();

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