<?php

namespace App\Utils;


/*
 * Clase para implementar los comandos a nivel de SO.
 * Sólo devuelve los strings con el comando generado.
 * Se ejecutan en la capa de SSH. Esta capa es sólo un
 * traductor.
 */

class Commands
{
    private $processes = [
        "java",
        "gecko",
        "firefox",
        "chrome",
        "php",
        "selenium",
        "Xvfb",
    ];
    private function runSyncCommand($command)
    {
        $output = exec('echo ' . getenv("BASH_PASS") . ') | sudo -u ' . getenv("BASH_USER") . ' -S echo ' . $command);
        return $output;
    }

    private function killProcessByName(String $pName)
    {
        return "pkill -9 \"$pName\"";
    }

    private function killProcessByPort(int $port)
    {
        return "fuser -k -n tcp $port";
    }

    private function processStatus($p)
    {
        $p = substr_replace($p, "[", 0, 0);
        $p = substr_replace($p, "]", 2, 0);
        return "ps aux | grep $p";
    }

    public function resetNavigator() {
        $this->killProcessByName("firefox");
        $this->killProcessByName("chrome");
        $this->killProcessByName("edge");
        $this->killProcessByName("safari");
    }

    public function wetherHeadless()
    {
        if ($GLOBALS['debug']) {
            return "export MOZ_HEADLESS=1";
        } else {
            return "export MOZ_HEADLESS=0";
        }
    }

    public function killBot()
    {
        $killSuccess = true;
        foreach($this->processes as $name => $process) {
            if(is_numeric($name)) {
                $killSuccess = $this->killProcessByPort($name);
            }
            else {
                $this->killProcessByName($name);
            }
        }

        $this->ssh->exec($this->commands->killProcessByPort(getenv("INTERNAL_SOCKETS_PORT")));
    }

    public function startX()
    {
        $this->ssh->exec("nohup Xvfb :99 -ac &");
    }

    public function startBot()
    {
        $this->ssh->exec("cd /var/www && sudo nohup php bin/console start-bot &");
    }

    public function getSession(): BotSession
    {
        $em = $this->container->get("doctrine.orm.entity_manager");
        return $em->createQueryBuilder()
            ->select('s')
            ->from('App:BotSession', 's')
            ->setMaxResults(1)
            ->orderBy('s.id', 'DESC')
            ->getQuery()->getSingleResult();
    }

    public function startSelenium()
    {
        /*
         * Capturar la sesión actual.
         */
        $sessionId = $this->getSession()->getId();
        $this->ssh->exec("cd /var/www && sudo geckodriver &");
        $this->ssh->exec("mkdir -p /var/www/debug/Selenium/$sessionId && DISPLAY=:99 nohup java -Dwebdriver.firefox.marionette=false -Dwebdriver.server.session.timeout=0 -jar /var/www/drivers/3.8.1.jar -enablePassThrough false -timeout 0 -port 50901 &> /var/www/debug/Selenium/$sessionId/sel.log");
    }

    public function getStatus()
    {
        return $this->ssh->exec($this->commands->processStatus("php bin/console start-bot"));
    }

    public function disconnect()
    {
        $this->ssh->disconnect();
    }
}