<?php

namespace App\Utils;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Firefox\FirefoxProfile;
use Facebook\WebDriver\Firefox\FirefoxDriver;

/*
 * Clase para implementar los comandos a nivel de SO.
 * Sólo devuelve los strings con el comando generado.
 * Se ejecutan en la capa de SSH. Esta capa es sólo un
 * traductor.
 */

class Commands
{
    public function killProcessByName(String $pName)
    {
        return "pkill -9 \"$pName\"";
    }

    public function killProcessByPort(int $port)
    {
        return "fuser -k -n tcp $port";
    }

    public function processStatus($p)
    {
        $p = substr_replace($p, "[", 0, 0);
        $p = substr_replace($p, "]", 2, 0);
        return "ps aux | grep $p";
    }

    public function mozillaHeadlessEnv($headless)
    {
        if ($headless) {
            return "export MOZ_HEADLESS=1";
        } else {
            return "export MOZ_HEADLESS=0";
        }
    }

    public function killBotProcess()
    {
        $this->ssh->exec($this->commands->killProcessByName("php bin/console start-bot"));
        $this->ssh->exec($this->commands->killProcessByName("java"));
        $this->ssh->exec($this->commands->killProcessByName("gecko"));
        $this->ssh->exec($this->commands->killProcessByName("selenium"));
        $this->ssh->exec($this->commands->killProcessByName("firefox"));
        $this->ssh->exec($this->commands->killProcessByName("Xvfb"));
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
        $this->ssh->exec("mkdir -p /var/www/debug/Selenium/$sessionId && DISPLAY=:99 nohup java -Dwebdriver.firefox.marionette=false -Dwebdriver.server.session.timeout=0 -jar /var/www/drivers/selenium-server-standalone-3.8.1.jar -enablePassThrough false -timeout 0 -port 50901 &> /var/www/debug/Selenium/$sessionId/sel.log");
    }

    public function getStatus()
    {
        return $this->ssh->exec($this->commands->processStatus("php bin/console start-bot"));
    }

    public function setBotHeadless($headless)
    {
        $this->ssh->exec($this->commands->mozillaHeadlessEnv($headless));
    }

    public function disconnect()
    {
        $this->ssh->disconnect();
    }
}