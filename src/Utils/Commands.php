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

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    private function runSyncCommand($command)
    {
        $output = exec('echo ' . getenv("BASH_PASS") . ' | sudo -u ' . getenv("BASH_USER") . ' -S ' . $command);
        return $output;
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

    private function processStatus($p)
    {
        $p = substr_replace($p, "[", 0, 0);
        $p = substr_replace($p, "]", 2, 0);
        return $this->runSyncCommand("ps aux | grep $p | awk \"{ print \$2 }\"");
    }

    public function isBotHanging()
    {
        $this->container->get("app.dblogger")->success("Process status: " . $this->processStatus("java"));
        if (!stristr($this->processStatus("php"), "php bin/console start-bot")
            || !stristr($this->processStatus("java"), "selenium-server") ) { //Bot is hanging since no proccess is running
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
            if ($GLOBALS['debug']) {
                $this->runSyncCommand("export MOZ_HEADLESS=1");
            } else {
                $this->runSyncCommand("export MOZ_HEADLESS=0");
            }
            $this->runAsyncCommand("nohup Xvfb :99", "/var/www/debug/Xvfb/$sessionId.log");
            $this->runSyncCommand("export DISPLAY=:99 && export DISPLAY=127.0.0.1:99");
            $this->runSyncCommand("export MOZ_CRASHREPORTER_SHUTDOWN=1");
            $this->runAsyncCommand("DISPLAY=:99 java -Dwebdriver.gecko.driver=/var/www/drivers/gecko/0.20.1 -Dwebdriver.server.session.timeout=99999999  -jar /var/www/drivers/selenium-server/3.8.1.jar -timeout 99999999 -enablePassThrough false", "/var/www/debug/Selenium/$sessionId/sel.log");
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