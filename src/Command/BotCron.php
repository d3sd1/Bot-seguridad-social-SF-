<?php

namespace App\Command;
set_time_limit(0);

use App\Entity\Queue;
use App\Utils\Commands;
use App\Utils\CommandUtils;
use Facebook\WebDriver\Chrome\ChromeDriver;
use Facebook\WebDriver\Exception\SessionNotCreatedException;
use Facebook\WebDriver\Exception\WebDriverCurlException;
use Facebook\WebDriver\Firefox\FirefoxDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Facebook\WebDriver\Chrome\ChromeOptions;

class BotCron extends ContainerAwareCommand
{
    private $em;
    private $bm;
    private $log;

    protected function configure()
    {
        $this
            ->setName("bot-cron")
            ->setDescription('Checks if the bot needs to be started.')
            ->addArgument('debug', InputArgument::OPTIONAL, 'Start on debug mode (must be instanciated on server GUI bash, either X won\'t be created and it will impolode).')
            ->setHelp('No help.');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $GLOBALS['debug'] = false;
        try {

            /*
             * Iniciar objetos.
             */
            $this->em = $this->getContainer()->get('doctrine')->getManager();
            $this->bm = $this->getContainer()->get('bot.manager');
            $this->log = $this->getContainer()->get('app.dblogger');

            $this->log->info("[CRON] Check if bot is hanging...");
            $actualAction = $this->em->getRepository("App:Queue")->findAll(array('id' => 'ASC'))[0];

            if ($this->getContainer()->get("so.commands")->isBotHanging()) {
                if ($this->getContainer()->get("bot.manager")->start(false)) {
                    $this->log->warning("[CRON] Bot Restarted success.");
                    $this->getContainer()->get("so.commands")->startBot();
                }
                else {
                    $this->log->error("[CRON] Bot Restarted FAILED.");
                }
            } // check if bot is hanging from timeouts on queue.
            else if (null != $actualAction && null != $actualAction->getDateAdded() && $actualAction->getDateAdded()->modify("+5 minutes") < (new \DateTime())) {
                $this->getContainer()->get('bot.manager')->setBotStatus('CRASHED');
                $this->log->error("[CRON] Bot Queue handged. Restarting server...");
            } else {
                $this->log->info("[CRON] Bot OK");
            }

        } catch (\Exception $e) {
            $this->log->error("Ha ocurrido un error interno en el comando de [reseteo estado]: " . $e->getMessage());
        }
    }
}