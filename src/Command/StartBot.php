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

class StartBot extends ContainerAwareCommand
{

    private $selenium = false;
    private $em;
    private $bm;
    private $log;
    private $processingQueue = true;

    protected function configure()
    {
        $this
            ->setName("start-bot")
            ->setDescription('Starts the bot.')
            ->addArgument('debug', InputArgument::OPTIONAL, 'Start on debug mode (must be instanciated on server GUI bash, either X won\'t be created and it will impolode).')
            ->setHelp('This command allows you to start the bot server, and the required automatizing scripts. You have to get Firefox installed and the bot running under nginx.');
    }

    /*
     * Procesar cola
     */
    public function processQueue()
    {
        $this->log->info("Comenzando procesamiento de cola!");
        /*
         * Preparar query para la cola.
         */
        $qb = $this->em->createQueryBuilder();
        $taskQuery = $qb->select(array('q'))
            ->from('App:Queue', 'q')
            ->orderBy('q.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery();
        while ($this->processingQueue) {
            /*
             * Reboot firefox
             */
            if ($this->bm->getBotStatus() === "SS_PAGE_DOWN") {
                $this->log->info("Página de la seguridad social inactiva. Esperando " . getenv("SS_PAGE_DOWN_SLEEP") . " segundos.");
                sleep(getenv("SS_PAGE_DOWN_SLEEP"));
            }
            //$this->getContainer()->get("so.commands")->resetNavigator();
            /*
             * Recuperar los resultados actuales.
             */
            $task = $taskQuery->getOneOrNullResult();

            /*
             * Si hay cosas por hacer, se hacen.
             * Si no, esperar a intervalos de 5s.
             */
            if ($task != null) {
                $this->bm->setBotStatus("RUNNING");
                $this->processTask($task);
            } else {
                $this->bm->setBotStatus("WAITING_TASKS");
                /* FIX: navegar a otra web para evitar el timeout de la seguridad social. */
                $this->selenium->get("http://www.google.es");
                while($task == null) {
                    sleep(5);
                    $task = $taskQuery->getOneOrNullResult();
                    $GLOBALS['OPERATION_TIMEOUT_SECONDS_SESS'] = 0; // Remove pending wait since we ended up with a queue! we dont want permanent timeout!
                }
            }
        }
        $this->log->info("Deteniendo loop de procesamiento de cola...");
    }

    /*
     * Procesa una tarea.
     */
    public function processTask(Queue $task)
    {
        /* Set bot in debug mode */
        try {
            /*
             * Cargar nombre de la clase del controlador
             * de Selenium.
             */
            $task = explode("_", $task->getProcessType()->getType());
            $taskPlainClass = "";
            foreach ($task as $part) {
                $taskPlainClass .= ucfirst(strtolower($part));
            }
            $taskClass = "\App\Selenium\\" . $taskPlainClass;

            /*
             * Cargar la operación requerida.
             */

            $qb = $this->em->createQueryBuilder();
            $taskData = $qb->select(array('t'))
                ->from('App:Queue', 'q')
                ->join("App:" . $taskPlainClass, "t", "WITH", "q.referenceId = t.id")
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();

            /*
             * Instanciar la automatización
             */
            $status = $this->em->getRepository("App:ProcessStatus")->findOneBy(['id' => $taskData->getStatus()])->getStatus();

            if($status == "AWAITING") {
                new $taskClass($taskData, $this->getContainer(), $this->em, $this->selenium);
            }

            while($status == "IN_PROCESS" || $status == "AWAITING") {
                sleep(2);
                $status = $this->em->getRepository("App:ProcessStatus")->findOneBy(['id' => $taskData->getStatus()])->getStatus();
            }

        } catch (\Exception $e) {
            $this->log->error("Ha ocurrido un error interno en el bot [BOT TASK MANAGER]: " . $e->getMessage());
        }
    }

    private function initSeleniumDriver()
    {

        /*
        * Iniciar Selenium driver
        */

        $this->bm->start(false);

        /*
         * CARGAR CONTROLADOR
         */
        try {
            $navigator = getenv("SELENIUM_NAVIGATOR");
            /*
             * Si se requiere de cambiar el certificado, simplemente cambiar el perfil de firefox.
             * Para ello, crear un perfil y exportarlo a zip y base 64.
             */
            switch ($navigator) {
                case "chrome":
                    $caps = DesiredCapabilities::chrome();
                    $options = new ChromeOptions();
                    $options->addArguments(array(
                        '--user-data-dir=/home/andrei/.config/google-chrome'
                    ));
                    $caps->setCapability(ChromeOptions::CAPABILITY, $options);

                    break;
                case "firefox":
                    $caps = DesiredCapabilities::firefox();
                    //$caps->setCapability('marionette', true);
                    $caps->setCapability('webdriver.gecko.driver', "/var/www/drivers/gecko/0.20.1");

                    $caps->setCapability(FirefoxDriver::PROFILE, file_get_contents('/var/www/drivers/profiles/firefox/profile.zip.b64'));
                    break;
                default:
                    $this->log->error("Unrecognized navigator (on .env config file): " + $navigator);
                    die();
            }
            $caps->setPlatform("Linux");
            $host = 'http://localhost:4444/wd/hub/';

            $this->selenium = RemoteWebDriver::create($host, $caps);
        } catch (SessionNotCreatedException $e) {
            $this->log->warning("Firefox drivers not loaded (GeckoDriver). Exiting bot.");
            exit();
        } catch (WebDriverCurlException $e) {
            $this->log->warning("Selenium driver not loaded (Did u loaded GeckoDriver?). Details: " . $e->getMessage());
            exit();
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $GLOBALS['debug'] = false;
        $GLOBALS['OPERATION_TIMEOUT_SECONDS_SESS'] = 0;
        try {

            /*
             * Iniciar objetos.
             */
            $this->em = $this->getContainer()->get('doctrine')->getManager();
            $this->bm = $this->getContainer()->get('bot.manager');
            $this->log = $this->getContainer()->get('app.dblogger');

            /*
             * Marcar estado del bot como iniciando.
             */
            $this->bm->setBotStatus("BOOTING");

            /*
             * Iniciar Selenium Driver.
             */
            $this->log->info("Cargando driver Selenium...");
            $this->initSeleniumDriver();
            $this->log->info("¡Cargado driver Selenium!");


            /*
             * Marcar estado del bot como iniciando.
             */
            $this->bm->setBotStatus("WAITING_TASKS");

            /*
             * Procesar cola.
             */
            $this->log->info("Cargando procesamiento de cola...");
            $this->processQueue();

        } catch (\Exception $e) {
            $this->log->error("Ha ocurrido un error interno en el comando de [procesamiento de cola]: " . $e->getMessage());
            $this->processingQueue = false;
        }
    }
}