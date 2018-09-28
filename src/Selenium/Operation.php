<?php

namespace App\Selenium;

use Facebook\WebDriver\WebDriverExpectedCondition;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Facebook\WebDriver\WebDriverBy;
use App\Entity\ServerStatus;

abstract class Operation
{
    protected $operation;
    protected $container;
    protected $em;
    protected $driver;
    protected $operationName;
    protected $server;

    public function __construct(\App\Entity\Operation $operation, ContainerInterface $container, EntityManager $em, ServerStatus $server)
    {
        try {

            $this->operation = $operation;
            $this->container = $container;
            $this->em = $em;
            $this->server = $server;
            $this->driver = $this->container->get("app.selenium")->getDriver();

            $this->operationName = array_values(array_slice(explode("\\", get_class($operation)), -1))[0];
            $this->container->get("app.dblogger")->success("Iniciando operación " . strtolower($this->operationName) . " ID: " . $this->operation->getId());
            $this->updateStatus("IN_PROCESS");
            $this->manageOperation();
        } catch (\Exception $e) {
            $this->container->get("app.dblogger")->error("El bot a crasheado. Motivo: " . $e->getMessage());
            /* FIX: Ahora reinicia el bot */

            //CERRAR
            $ssh = $this->get("app.ssh");
            if (!$ssh->connect()) {
                return $this->container->get("response")->error(500, "SERVER_NOT_CONFIGURED");
            }
            /*
             * Matar todos procesos el bot que estén corriendo.
             */
            $ssh->killBotProcess();
            $ssh->disconnect();

            /*
             * Marcar servidor como inactivo
             */
            $em = $this->get("doctrine.orm.entity_manager");
            $qb = $em->createQueryBuilder();
            $server = $qb->select(array('s'))
                ->from('App:ServerStatus', 's')
                ->orderBy('s.id', 'ASC')
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();
            if ($server !== null) {
                $server->setCurrentStatus($em->getRepository("App:ServerStatusOptions")->findOneBy(['status' => "OFFLINE"]));
                $em->flush();
            }
            $this->get("app.dblogger")->success("Servidor detenido.");

            //INICIAR


            $em = $this->get("doctrine.orm.entity_manager");
            /*
             * Iniciar estado del servidor.
             * Dejar sólo un estado del bot (prevenir duplicados).
             * Marcar el estado como running, y resetear
             * los demás valores.
             */

            $em->createQueryBuilder()
                ->delete('App:ServerStatus', 's')
                ->where('s.id != :serverId')
                ->setParameter('serverId', 1)
                ->getQuery()->execute();

            $bootServer = $em->getRepository("App:ServerStatus")->findOneBy(['id' => 1]);
            if ($bootServer === null) {
                $bootServer = new ServerStatus();
            }

            /* Abortar todas las peticiones previas */

            $getQueue = $qb->select(array('q'))
                ->from('App:Queue', 'q')
                ->orderBy('q.id', 'ASC')
                ->setMaxResults(1)
                ->getQuery()->getArrayResult();
            $abortedStatus = $em->getRepository("App:ProcessStatus")->findOneBy(['status' => "ABORTED"]);
            foreach($getQueue as $queueProccess) {
                /* Marcar como abortada */
                $queueProccess->setStatus($abortedStatus->getId());
                /* Eliminar de la cola */
                $em->remove($queueProccess);
            }
            $em->flush();

            $bootServer->setId(1);
            $bootServer->setCurrentStatus($em->getRepository("App:ServerStatusOptions")->findOneBy(['status' => 'BOOTING']));
            $bootServer->setSessionAlerts(0);
            $bootServer->setSessionErrors(0);
            $bootServer->setSessionProcessedRequests(0);
            $bootServer->setSessionWarnings(0);
            $em->persist($bootServer);
            $em->flush();

            $ssh = $this->get("app.ssh");
            if (!$ssh->connect()) {
                return $this->container->get("response")->error(500, "SERVER_NOT_CONFIGURED");
            }

            /*
             * Iniciar sesión del bot.
             */
            $botSession = new BotSession();
            $botSession->setDatetime();
            $em->persist($botSession);
            $em->flush();
            /*
             * Matar todos procesos el bot que estuvieran corriendo antes.
             */
            $ssh->killBotProcess();

            /*
             * Establecer modo headless.
             */
            $ssh->setBotHeadless(true);

            /*
             * Abrir interfaz gráfica.
             */
            $ssh->startX();

            /*
             * Iniciar el bot y selenium.
             */
            $ssh->startSelenium();
            $ssh->startBot();

            $this->get("app.dblogger")->success("Servidor iniciado.");
            $ssh->disconnect();

            /* ENDFIX */
            $this->setServerStatus("CRASHED");
            exit();
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

    /*
     * Manda el estado del servidor (String)
     */
    public function setServerStatus($serverStatus)
    {
        $this->server->setCurrentStatus($this->em->getRepository("App:ServerStatusOptions")->findOneBy(['status' => $serverStatus]));
        $this->em->flush();
    }

    private function manageOperation()
    {
        $this->container->get("app.dblogger")->info("OP INTERNAL NAME: " . strtoupper($this->operationName));
        getenv('FORCE_PROD_SS_URL') ? $env = "PROD" : $env = $this->container->get('kernel')->getEnvironment();
        $reqUrl = (new \ReflectionClass('App\Constants\\' . ucfirst(strtolower($env)) . 'UrlConstants'))->getConstant(strtoupper($this->operationName));
        $this->container->get("app.dblogger")->info("OP SS URL: " . $reqUrl);
        if ($this->checkPageAvailable($reqUrl)) {
            $this->driver->get($reqUrl);
            if ($this->doOperation()) {
                $this->updateStatus("COMPLETED");
                $this->removeFromQueue();
            } else {
                $this->updateStatus("ERROR");
                $this->removeFromQueue();
            }
        } else {
            $this->updateStatus("STOPPED");
            $this->setServerStatus("SS_PAGE_DOWN");
            $this->container->get("app.dblogger")->info("La página de la seguridad social no está activa.");
        }

        $this->container->get("app.dblogger")->success("Fin de operación " . strtolower($this->operationName) . " ID: " . $this->operation->getId());
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
            $this->driver->wait(6, 300)->until(
                WebDriverExpectedCondition::visibilityOfElementLocated($expected)
            );
            $this->container->get("app.dblogger")->info("Envío satisfactorio. Comprobando errores.");
            $found = true;
        } catch (\Exception $e) {
            $this->container->get("app.dblogger")->info("Envío erróneo: " . $e->getMessage());
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

    protected function removeFromQueue()
    {
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
            $this->container->get("app.dblogger")->warning("Comprobando errores...");
        }

        /* Primero comprobar errores críticos de la web */
        try {
            $this->driver->findElement(WebDriverBy::id('Static001'));
            $this->updateStatus("STOPPED");
            $this->setServerStatus("SS_PAGE_DOWN");
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
            ];
            if ($consultaAltasCCC) {
                $notErrorBoxCodes[3543] = "NO EXISTEN DATOS PARA ESTA CONSULTA";
                $notErrorBoxCodes[3251] = "HAY MAS AFILIADOS A CONSULTAR";
                $notErrorBoxCodes[3083] = "INTRODUZCA LOS DATOS Y PULSE CONTINUAR";
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
                    $this->container->get("app.dblogger")->error("Error en operación: " . $errorBoxes->getText());
                }
                return true;
            }
        } catch
        (\Exception $e) {
            if ($log) {
                $this->container->get("app.dblogger")->info("Formulario sin errores por exception.");
            }
        }
        return false;
    }
}