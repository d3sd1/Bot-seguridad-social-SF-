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

class ConsultaIpf extends Operation
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
         * Rellenar número de afiliación
         * Primer campo, dos dígitos INT
         * Segundo campo, diez dígitos INT
        */
        $this->driver->findElement(WebDriverBy::name('txt_SDFPROVANT'))->sendKeys(substr($this->operation->getNaf(), 0, 2));
        $this->driver->findElement(WebDriverBy::name('txt_SDFNUMANT'))->sendKeys(substr($this->operation->getNaf(), 2, 10));

        /*
         * Clickar en el botón de enviar
         * Aquí concluye el formulario
         */
        $this->takeScreenShoot();
        $this->driver->findElement(WebDriverBy::name('btn_Sub2207201008'))->click();

        /*
         * Esperar a que se envíe el formulario.
         */
        $this->waitFormSubmit(WebDriverBy::id('SDFCODNUE'));

        /*
         * Revisar si hay errores en el formulario. Si los hay, detener ejecución.
         */
        if ($this->hasFormErrors()) {
            return false;
        }

        /*
         * Comprobar si hay errores específicos, ya que en esta consulta
         * tienen otra excepción que no tiene nada que ver con los demás.
         */
        if (stristr($this->driver->findElement(WebDriverBy::id('SDFNOMBRE'))->getText(), "NUMERO DE AFILIACION INEXISTENTE")) {
            $this->updateStatus("ERROR");
            $this->operation->setErrMsg($this->driver->findElement(WebDriverBy::id('SDFNOMBRE'))->getText());
            $this->removeFromQueue();
            return false;
        } else {
            /*
             * Guardar su NAF en la base de datos.
             */
            $this->updateConsultaData(
                json_encode(array(
                    "ipt" => $this->driver->findElement(WebDriverBy::id('SDFIPFTIPO'))->getText(),
                    "ipf" => $this->driver->findElement(WebDriverBy::id('SDFIPFNUM'))->getText(),
                    "naf" => $this->driver->findElement(WebDriverBy::id('SDFNOMBRE'))->getText()
                ))
            );
        }

        /*
         * Revisar si hay errores en el formulario. Si los hay, marcar como inválido.
         */
        return true;
    }
}