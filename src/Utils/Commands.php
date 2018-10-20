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
    private $processes = [
        "java",
        "gecko",
        "firefox",
        "chrome",
        "php" => [
            "command" => 'cd /var/www && sudo nohup php bin/console start-bot',
            "async" => "true",
            "pipe" => null
        ],
        "selenium" => [
            "command" => "mkdir -p /var/www/debug/Selenium/{{sessionId}} && DISPLAY=:99 nohup java -Dwebdriver.server.session.timeout=99999999 -jar 3.14.0.jar -timeout 99999999",
            "async" => true,
            "pipe" => "&> /var/www/debug/Selenium/{{sessionId}}/sel.log"
        ],
        "Xvfb"  => [
            "command" => 'nohup Xvfb :99 -ac',
            "async" => true,
            "pipe" => null
        ],
    ];
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->processes[getenv("INTERNAL_SOCKETS_PORT")] = null;
    }

    private function runSyncCommand($command)
    {
        $output = exec('echo ' . getenv("BASH_PASS") . ' | sudo -u ' . getenv("BASH_USER") . ' -S echo ' . $command);
        return $output;
    }
    private function runAsyncCommand($command, $pipeRedir = null)
    {
        if($pipeRedir === null) {
            $pipeRedir = '&';
        }
        $output = exec('echo ' . getenv("BASH_PASS") . ' | sudo -u ' . getenv("BASH_USER") . ' -S echo ' . $command.' '.$pipeRedir);
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
                $killSuccess ? $killSuccess = $this->killProcessByPort($name):$this->killProcessByPort($name);
            }
            else {
                $killSuccess ? $killSuccess = $this->killProcessByName($name):$this->killProcessByName($name);
            }
        }

        return $killSuccess;
    }

    public function startBot()
    {
        /*
         * Kill previous running stuff, just for secure
         */
        $this->killBot();
        $startSuccess = true;
        $sessionId = $this->container->get('bot.manager')->getSession()->getId();
        foreach($this->processes as $name => $process) {
            $pCommand = $process["command"];
            $async = $process["async"];
            $pipe = $process["pipe"];
            if($name == "selenium") {
                $pCommand = str_replace('{{sessionId}}', $sessionId, $pCommand);
                $pipe = str_replace('{{sessionId}}', $sessionId, $pipe);
            }
            if($process !== null) {
                if($async) {
                    $startSuccess ? $startSuccess = $this->runAsyncCommand($pCommand, $pipe):$this->runAsyncCommand($pCommand);
                }
                else {
                    $startSuccess ? $startSuccess = $this->runSyncCommand($pCommand, $pipe):$this->runSyncCommand($pCommand);
                }
            }
        }

        return $startSuccess;
    }

}