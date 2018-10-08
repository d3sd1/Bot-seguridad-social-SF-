<?php

namespace App\Utils;

use App\Entity\BotSession;
use Symfony\Component\DependencyInjection\ContainerInterface;
use phpseclib\Net\SSH2;

/*
 * Clase para simplificar la capa SO - BOT
 */

class BotSsh
{
    private $ssh;
    private $container;
    private $commands;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->commands = new Commands();
    }

    public function connect()
    {
        /*
         * Iniciar SSH para tener privilegios de administrador
         */
        try {
            $this->ssh = new SSH2(getenv("INTERNAL_SSH_HOST"), getenv("INTERNAL_SSH_PORT"));
            return $this->ssh->login(getenv("INTERNAL_SSH_USER"), getenv("INTERNAL_SSH_PASS"));
        } catch (\Exception $e) {
            $this->container->get("app.dblogger")->error(400, "No se pudo conectar por SSH. El bot controller por rest está inoperativo.");
            exit();
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
        $this->ssh->exec("mkdir -p /var/www/debug/Selenium/$sessionId && DISPLAY=:99 nohup java -Dwebdriver.firefox.marionette=false -Dwebdriver.server.session.timeout=0 -jar /var/www/drivers/selenium-server-standalone-3.14.0.jar -enablePassThrough false -timeout 0 -port 50901 &> /var/www/debug/Selenium/$sessionId/sel.log");
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