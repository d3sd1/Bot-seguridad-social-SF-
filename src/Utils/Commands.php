<?php

namespace App\Utils;


/*
 * Clase para implementar los comandos a nivel de SO.
 * Sólo devuelve los strings con el comando generado.
 * Se ejecutan en la capa de SSH. Esta capa es sólo un
 * traductor.
 */

use Psr\Container\ContainerInterface;

class Commands
{
    /*
     * NOTA:
     * Se usa el driver 3.8.1, a partir del 3.11 sólo funcionan bien en java porque ya no admite el parámetro enablepasstrought y no reconoce los elementos.
     */
    private $container;
    private $bm;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->bm = $this->container->get('bot.manager');
    }

    private function runSyncCommand($command)
    {
        $output = exec('echo ' . getenv("BASH_PASS") . ' | sudo -u ' . getenv("BASH_USER") . ' -S ' . $command);
        return $output;
    }

    public function takeXScreenshoot($screenshot_name)
    {
        $this->runSyncCommand("DISPLAY=:99 import -window root $screenshot_name");
    }

    private function runAsyncCommand($command, $outputFile = null)
    {
        if ($outputFile === null) {
            $outputFile = '/dev/null';
        }
        //TODO: no funcioa redirigir a otra cosa que no sea /dev/null. si rediriges a un fichero.log no funciona.
        $outputFile = '/dev/null';//eliminar esto si se arregla el todo de arrbia.
        exec('echo ' . getenv("BASH_PASS") . ' | sudo -u ' . getenv("BASH_USER") . ' -S ' . $command . '  >' . $outputFile . ' 2>&1 &');
        return true;
    }

    private function killProcessByName(String $pName)
    {
        return $this->runSyncCommand("pkill -9 \"$pName\"");
    }

    private function killProcessByPort(int $port)
    {
        return $this->runSyncCommand("fuser -k -n tcp $port");
    }

    private function processRunning($p)
    {
        $result = $this->runSyncCommand("ps aux | grep \"$p\" | grep -v \"grep\"");
        if (stristr($result, $p) === false) {
            return false;
        }
        return true;
    }

    public function isBotHanging()
    {
        if (
            $this->bm->getBotStatus()->getCurrentStatus()->getStatus() != "BOOTING" &&
            $this->bm->getBotStatus()->getCurrentStatus()->getStatus() != "AWAITING" &&
            (!$this->processRunning("php bin/console start-bot")
                || !$this->processRunning("selenium-server"))
        ) { //Bot is hanging since no proccess is running
            $this->container->get("app.dblogger")->warning("Bot hanging... Taking it out. (isBotHanging())");
            return true;
        }
        return false;
    }

    public function resetNavigator()
    {
        try {
            $this->killProcessByName("firefox");
            $this->killProcessByName("chrome");
            $this->killProcessByName("edge");
            $this->killProcessByName("safari");
        } catch (\Exception $e) {
            $this->container->get("app.dblogger")->success("Excepción al resetear navegadores: " . $e->getMessage());
            return false;
        }
        return true;
    }

    public function restartServerSO()
    {
        $this->runAsyncCommand("reboot now");
    }

    public function killBot()
    {
        try {
            $this->killProcessByPort(4444);
            $this->killProcessByName("java");
            $this->killProcessByName("gecko");
            $this->killProcessByName("firefox");
            $this->killProcessByName("chrome");
            $this->killProcessByName("php");
            $this->killProcessByName("selenium");
            $this->killProcessByName("Xvfb");
        } catch (\Exception $e) {
            $this->container->get("app.dblogger")->success("Excepción al matar al bot: " . $e->getMessage());
            return false;
        }

        return true;
    }

    public function startBot()
    {
        /*
         * Kill previous running stuff, just for secure
         */
        try {
            $sessionId = $this->container->get('bot.manager')->getSession()->getId();

            $this->runSyncCommand("mkdir -p /var/www/debug/Xvfb");
            $this->runSyncCommand("touch /var/www/debug/Xvfb/$sessionId.log");
            $this->runSyncCommand("mkdir -p /var/www/debug/Selenium");
            $this->runSyncCommand("touch /var/www/debug/Selenium/$sessionId.log");
            $headless = getenv('HEADLESS');
            if ($headless) {
                $this->runSyncCommand("export MOZ_HEADLESS=1");
                $this->runAsyncCommand("nohup Xvfb :99 -screen 0 1024x768x24", "/var/www/debug/Xvfb/$sessionId.log");
                $this->runSyncCommand("export DISPLAY=:99 && export DISPLAY=127.0.0.1:99");
            } else {
                $this->runSyncCommand("export MOZ_HEADLESS=0");
            }
            $this->runSyncCommand("export MOZ_CRASHREPORTER_SHUTDOWN=1");
            /* El error de los reinicios es por el timeout. -1 peta, 0 peta. y este es el max valor. */
            $this->runAsyncCommand(($headless ? "DISPLAY=:99":"")." java -Dwebdriver.gecko.driver=/var/www/drivers/gecko/0.20.1 -Dwebdriver.server.session.timeout=2147480647  -jar /var/www/drivers/selenium-server/3.8.1.jar -timeout 2147480647 -enablePassThrough false", ""); //"/var/www/debug/Selenium/$sessionId/sel.log"
            $this->runSyncCommand("cd /var/www && php bin/console cache:clear");
            sleep(5); //Esperar a que cargue Selenium
            exec("cd /var/www && (nohup php bin/console start-bot >/dev/null 2>&1 &)");
            $this->container->get("app.dblogger")->success("Iniciado bot correctamente.");
        } catch (\Exception $e) {
            $this->container->get("app.dblogger")->error("Excepción al iniciar al bot: " . $e->getMessage());
            return false;
        }

        return true;
    }

    public function runCronChecker()
    {
        $this->runSyncCommand("cd /var/www && php bin/console bot-cron");
    }

}