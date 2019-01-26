<?php

namespace App\Selenium;

use Facebook\WebDriver\WebDriverExpectedCondition;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\Remote\LocalFileDetector;
use App\Entity\ServerStatus;

abstract class Operation
{
    protected $operation;
    protected $container;
    protected $em;
    protected $bm;
    protected $driver;
    protected $operationName;
    protected $server;

    public function __construct(\App\Entity\Operation $operation, ContainerInterface $container, EntityManager $em, $seleniumDriver)
    {
        try {

            $this->operation = $operation;
            $this->container = $container;
            $this->em = $em;
            $this->bm = $container->get('bot.manager');
            $this->server = $this->bm->getBotStatus();
            $this->driver = $seleniumDriver;

            $this->operationName = array_values(array_slice(explode("\\", get_class($operation)), -1))[0];

            if ($this->em->getRepository("App:ProcessStatus")->findOneBy(['id' => $this->operation->getStatus()])->getStatus() != "IN_PROCESS" &&
                $this->em->getRepository("App:ProcessStatus")->findOneBy(['id' => $this->operation->getStatus()])->getStatus() == "AWAITING") {
                $this->container->get("app.dblogger")->success("Iniciando operación " . strtolower($this->operationName) . " ID: " . $this->operation->getId());

                /*
                 * Comprobar que la tarea no está caducada y que
                 * hay algo por hacer.
                 */

                if ($this->operation->getDateInit() != null && $this->operation->getDateInit()->diff(new \DateTime())->s > getenv('OPERATION_TIMEOUT_SECONDS')) {
                    /* Eliminar de la cola */
                    $this->removeFromQueue();

                    /* Marcar operación como TIMED_OUT */
                    $this->updateStatus("TIMED_OUT");
                } else {
                    /* Si no, procesar operación */
                    $this->updateStatus("IN_PROCESS");
                    $this->manageOperation();
                }

            }

        } catch (\Exception $e) {

            /* ENDFIX */
            $this->bm->setBotStatus("CRASHED");

            if ($e->getMessage() == 'Notice: Undefined index: ELEMENT') {
                $this->container->get("app.dblogger")->error("El bot ha crasheado. Motivo: El certificado no está instalado en en el navegador o este ha sufrido problemas.");
            } //CRASH PREVENTED
            else if (!$this->server->getCrashPrevented()) {
                $this->bm->setBotStatus("OFFLINE");
                $this->container->get("app.dblogger")->warning("Crash prevented: restarting bot...");
                $this->container->get("app.dblogger")->warning("Crash prevented msg: " . $e->getMessage());
                $this->container->get("app.dblogger")->sendErrorMail("CRASH", "El bot ha crasheado (forzado reinicio). Motivo: " . $e->getMessage());
                $this->container->get("bot.manager")->close();
                sleep(20);
                $this->container->get("bot.manager")->start(false);
            } else {
                $this->container->get("app.dblogger")->error("El bot ha crasheado. URL: " . $this->driver->url() . " Motivo: " . $e->getMessage());
                $this->container->get("app.dblogger")->info("SOURCE: " . $this->driver->getPageSource());
            }
            $this->takeScreenShoot();
            $this->bm->close();
            $this->bm->start();
        }
    }

    private function checkPageAvailable($url)
    {
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $output = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpcode >= 400 && $httpcode <= 599) {
                return false;
            } else {
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    private function manageOperation()
    {
        getenv('FORCE_PROD_SS_URL') ? $env = "PROD" : $env = $this->container->get('kernel')->getEnvironment();
        $reqUrl = (new \ReflectionClass('App\Constants\\' . ucfirst(strtolower($env)) . 'UrlConstants'))->getConstant(strtoupper($this->operationName));
        $this->container->get("app.dblogger")->info("OP SS URL: " . $reqUrl);
        if ($this->checkPageAvailable($reqUrl)) {
            $this->driver->get($reqUrl);
            sleep(10);
            if ($this->doOperation()) {
                $this->updateStatus("COMPLETED");
                $this->removeFromQueue();
            } else {
                $this->updateStatus("ERROR");
                $this->removeFromQueue();
            }
        } else {
            $this->updateStatus("STOPPED");
            $this->bm->setBotStatus("SS_PAGE_DOWN");
            $this->container->get("app.dblogger")->info("La página de la seguridad social no está activa.");
        }

        $this->container->get("app.dblogger")->success("Fin de operación " . strtolower($this->operationName) . " ID: " . $this->operation->getId() . ". Resolución: " . $this->em->getRepository("App:ProcessStatus")->findOneBy(['id' => $this->operation->getStatus()])->getStatus());
        /* Marcar crash prevented como false ya que se ha realizado correctamente la operación previa que antes si crasheó */
        $this->server->setCrashPrevented(false);
        $this->em->flush();
    }

    abstract function doOperation();

    public function clearTmpFolder()
    {
        $files = glob('/var/www/tmp/*', GLOB_BRACE);
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function waitTmpDownload()
    {
        $tmpDir = "/var/www/tmp";
        $pdfDir = "/var/www/pdf";
        $filesFound = false;
        $attempts = 0;
        $destinationFolder = $pdfDir . "/" . $this->operationName;
        if (!file_exists($destinationFolder)) {
            mkdir($destinationFolder);
        }
        while (!$filesFound) {
            $files = array_diff(scandir($tmpDir), array('.', '..'));
            $this->container->get("app.dblogger")->info("Escaneando archivos ($attempts de 100).");
            if (count($files) > 0) {
                rename($tmpDir . "/" . array_values($files)[0], $destinationFolder . "/" . $this->operation->getId() . ".pdf");
                /*
                 * Guardar su base 64 del pdf en la base de datos.
                 */
                $this->updateConsultaData(
                    $destinationFolder . "/" . $this->operation->getId() . ".pdf"
                );
                $filesFound = true;
                break;
            }
            $attempts++;
            if ($attempts > 100) {
                break;
            } else {
                sleep(1);
            }
        }
        return $filesFound;
    }

    public function takeScreenShoot($element = null)
    {
        if ($this->container->get('kernel')->getEnvironment()) {
            $path = $this->container->get('kernel')->getRootDir() . "/../debug/" . $this->operationName . "/" . $this->operation->getId();
            if (!is_dir($path)) {
                $this->container->get("app.dblogger")->info("Creada carpeta para la operación y su pantallazo.");
                mkdir($path, 0777, true);
            }


            $screenshot = $path . "/page_" . microtime(true) . ".png";

            // Change the driver instance
            $this->driver->takeScreenshot($screenshot);
            if (!file_exists($screenshot)) {
                throw new Exception('Could not save screenshot');
            }
            $this->container->get("app.dblogger")->info("Capturando pantalla del formulario: " . $screenshot);

            if (!(bool)$element) {
                return $screenshot;
            }

            $element_screenshot = $path . "/element_" . microtime(true) . ".png";

            $element_width = $element->getSize()->getWidth();
            $element_height = $element->getSize()->getHeight();

            $element_src_x = $element->getLocation()->getX();
            $element_src_y = $element->getLocation()->getY();

            // Create image instances
            $src = imagecreatefrompng($screenshot);
            $dest = imagecreatetruecolor($element_width, $element_height);

            // Copy
            imagecopy($dest, $src, 0, 0, $element_src_x, $element_src_y, $element_width, $element_height);

            imagepng($dest, $element_screenshot);

            // unlink($screenshot); // unlink function might be restricted in mac os x.

            if (!file_exists($element_screenshot)) {
                throw new Exception('Could not save element screenshot');
            }

            return $element_screenshot;
        }
        return false;
    }

    protected function waitFormSubmit($expected)
    {
        $this->container->get("app.dblogger")->info("Esperando envío del formulario...");
        try {
            $this->container->get("app.dblogger")->info("Comprobando envío...");
            $this->driver->wait(10, 300)->until(
                WebDriverExpectedCondition::visibilityOfElementLocated($expected)
            );
            $this->container->get("app.dblogger")->info("Envío satisfactorio. Comprobando errores.");
            $found = true;
        } catch (\Exception $e) {
            $this->container->get("app.dblogger")->info("Envío erróneo (Si hay otro error de envío, este es asociado y no principal): " . $e->getMessage());
            $this->container->get("app.dblogger")->info("SOURCE: " . $this->driver->getPageSource());
            $found = false;
        }
        return $found;
    }

    /*
     * Actualizar el estado.
     */
    protected function updateStatus($status)
    {
        $this->operation->setStatus($this->em->getRepository("App:ProcessStatus")->findOneBy(['status' => $status])->getId());
    }

    public function updateConsultaData($data)
    {
        $this->operation->setData($data);
    }

    private function setProcessTime() {
        $this->operation->updateProcessTime();
    }

    protected function removeFromQueue()
    {
        $this->setProcessTime();
        /*
         * Eliminar de la cola.
         */
        $this->em->createQueryBuilder()
            ->delete('App:Queue', 'q')
            ->where('q.referenceId=:refId')
            ->setParameter('refId', $this->operation->getId())
            ->getQuery()
            ->execute();
        $this->em->flush();
    }

    protected function hasFormErrors($log = true, $consultaAltasCCC = false)
    {
        if ($log) {
            $this->container->get("app.dblogger")->info("Comprobando errores...");
        }

        /* Primero comprobar errores críticos de la web */
        try {
            $this->driver->findElement(WebDriverBy::id('Static001'));
            $this->updateStatus("STOPPED");
            $this->bm->setBotStatus("SS_PAGE_DOWN");
            $this->container->get("app.dblogger")->info("La página de la seguridad social está en mantenimiento.");
            return true;
        } catch (\Exception $e) {

        }
        try {
            $errorBoxes = $this->driver->findElement(WebDriverBy::id('DIL'));
            /*
             * ¿Hay errores?
             * Puede ser que aparezca la caja para determinar que se hizo correctamente.
             * Para ello, guardamos los códigos satisfactorios y les asociamos una descripción.
             */
            $notErrorBoxCodes = [
                3408 => "Operación realizada correctamente (alta)",
                3083 => "INTRODUZCA LOS DATOS Y PULSE CONTINUAR",
                9125 => "ALTA REALIZADA. ASIGNADO CONVENIO DE LA CUENTA",
            ];
            if ($consultaAltasCCC) {
                $notErrorBoxCodes[3543] = "NO EXISTEN DATOS PARA ESTA CONSULTA";
                $notErrorBoxCodes[3251] = "HAY MAS AFILIADOS A CONSULTAR";
                $notErrorBoxCodes[3083] = "INTRODUZCA LOS DATOS Y PULSE CONTINUAR";
                $notErrorBoxCodes[9125] = "ALTA REALIZADA. ASIGNADO CONVENIO DE LA CUENTA";
                $notErrorBoxCodes[4359] = "MOVIMIENTO PREVIO ERRONEO - AFILIADO EN ALTA PREVIA";
            }
            $isFalseError = false;
            foreach ($notErrorBoxCodes as $code => $desc) {
                if (stristr($errorBoxes->getText(), $code . '*') !== false) {
                    $isFalseError = true;
                }
            }
            if ($log) {
                $this->container->get("app.dblogger")->info("REVISANDO ERRORES...");
            }
            if ($errorBoxes->isDisplayed() && !$isFalseError) {
                if ($log) {
                    $this->container->get("app.dblogger")->warning("Error del formulario encontrado.");
                    $this->updateStatus("ERROR");
                    $this->operation->setErrMsg($errorBoxes->getText());
                    $this->em->flush();
                    $this->removeFromQueue();
                    $this->container->get("app.dblogger")->warning("Error en operación: " . $errorBoxes->getText());
                }
                return true;
            }
        } catch
        (\Exception $e) {
            if ($log) {
                $this->container->get("app.dblogger")->error("Formulario sin errores por exception: " + $e->getMessage());
            }
        }
        return false;
    }
}