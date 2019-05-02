<?php

namespace App\Command;
set_time_limit(60);

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

class BotTester extends ContainerAwareCommand
{
    private $em;
    private $bm;
    private $log;

    protected function configure()
    {
        $this
            ->setName("bot-tester")
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

            if (getenv('RUN_TESTS_AUTO') == "true") {
                $this->log->info("[CRON-TESTER] Bot running test!");

                $curl = curl_init();
                $method = "POST";
                $url = "http://192.168.1.32/consulta/naf";
                $data = '       {
           "ipt": "0' . rand(1, 6) . '",
           "ipf": "5345706' . rand(8, 9) . 'D",
           "ap1": "GARCIA",
           "ap2": "CUADRA"
       }';
                switch ($method) {
                    case "POST":
                        curl_setopt($curl, CURLOPT_POST, 1);

                        if ($data)
                            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                        break;
                    case "PUT":
                        curl_setopt($curl, CURLOPT_PUT, 1);
                        break;
                    default:
                        if ($data)
                            $url = sprintf("%s?%s", $url, http_build_query($data));
                }

                // Optional Authentication:
                curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($curl, CURLOPT_USERPWD, "username:password");

                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

                /* 50% percentage to perform request */
                if (rand(1, 10) > 5) {
                    $result = curl_exec($curl);
                    $this->log->info("[CRON-TESTER] Run bot test sucess. Data: " . $data);
                }

                curl_close($curl);


            }

        } catch (\Exception $e) {
            $this->log->error("Ha ocurrido un error interno en el comando de [reseteo estado]: " . $e->getMessage());
        }
    }
}