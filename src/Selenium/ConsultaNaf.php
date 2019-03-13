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

class ConsultaNaf extends Operation
{

    public function doOperation()
    {
        $this->container->get("app.dblogger")->info("Rellenando formulario...");
        /*
         * **************************************
         * Rellenar formulario.
         * **************************************
         */

        /*
         * Rellenar IPF (Tipo e identificador).
         * Primer campo, dos dígitos INT
         * Segundo campo, diez dígitos INT
        */
        $this->driver->findElement(WebDriverBy::name('txt_SDFTIPO_ayuda'))->sendKeys($this->operation->getIpt());
        $this->driver->findElement(WebDriverBy::name('txt_SDFNUMERO'))->sendKeys($this->operation->getIpf());

        /*
         * Rellenar apellidos.
         * Primer campo, primer apellido.
         * Segundo campo, segundo apellido.
         */
        $this->driver->findElement(WebDriverBy::name('txt_SDFAPELL1'))->sendKeys($this->operation->getAp1());
        $this->driver->findElement(WebDriverBy::name('txt_SDFAPELL2'))->sendKeys($this->operation->getAp2());

        /*
         * Clickar en el botón de enviar
         * Aquí concluye el formulario
         */
        $this->takeScreenShoot();
        $this->driver->findElement(WebDriverBy::name('btn_Sub2207601004'))->click();

        /*
         * Esperar a que se envíe el formulario.
         */
        $this->waitFormSubmit(WebDriverBy::id('SDFPROVNAF'));

        /*
         * Revisar si hay errores en el formulario. Si los hay, detener ejecución.
         */
        if ($this->hasFormErrors()) {
            return false;
        }

        /*
         * Guardar su NAF en la base de datos.
         */
        $this->updateConsultaData(
            $this->driver->findElement(WebDriverBy::id('SDFPROVNAF'))->getText() .
            $this->driver->findElement(WebDriverBy::id('SDFNUMNAF'))->getText()
        );
        /*
         * Revisar si hay errores en el formulario. Si los hay, marcar como inválido.
         */
        return true;
    }
}