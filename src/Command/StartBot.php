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

    private $processQueue = true;
    private $socket = false;
    private $selenium = false;
    private $em;
    private $bm;
    private $log;

    protected function configure()
    {
        $this
            ->setName("start-bot")
            ->setDescription('Starts the bot.')
            ->addArgument('debug', InputArgument::OPTIONAL, 'Start on debug mode (must be instanciated on server GUI bash, either X won\'t be created and it will impolode).')
            ->setHelp('This command allows you to start the bot server, and the required automatizing scripts. You have to get Firefox installed and the bot running under nginx.');
    }

    /*
     * Iniciar escuchadores y prevenir que se deniegue la ejecución.
     */
    public function socketCreate()
    {
        if ($this->socket === false) {
            try {
                $this->socket = socket_create(AF_INET, SOCK_STREAM, 0);
                socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
                socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 0, 'usec' => 0));
                socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 0, 'usec' => 0));
                socket_bind($this->socket, getenv("INTERNAL_SOCKETS_HOST"), getenv("INTERNAL_SOCKETS_PORT"));
                socket_listen($this->socket, 3);
            } catch (\Exception $e) {
                $this->log->error("El servidor ya estaba iniciado.");
                exit();
            }
        }
        return $this->socket;
    }

    /*
     * Cerrar escuchadores.
     */
    public function socketKill()
    {
        socket_close($this->socket);
    }

    /*
     * Esperar a operación por sockets.
     */
    public function waitForTask()
    {
        try {
            $spawn = socket_accept($this->socket);
            $input = socket_read($spawn, 1024);
            $data = trim($input);
            socket_close($spawn);
        } catch (\Exception $e) {
            return false;
        }
        return true;
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

            new $taskClass($taskData, $this->getContainer(), $this->em, $this->selenium);

        } catch (\Exception $e) {
            var_dump($e->getMessage());die();
            $this->log->error("Ha ocurrido un error interno en el bot [BOT TASK MANAGER]: " . $e->getMessage());
        }
    }

    private function initSeleniumDriver()
    {

        /*
        * Iniciar Selenium driver
        */

        $this->bm->start();

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
                    $caps->setCapability('marionette', true);
                    $caps->setCapability('webdriver.gecko.driver', "/usr/bin/geckodriver");
                    if (!$GLOBALS["debug"]) {
                        $this->bm->setHeadlessEnv();
                    }

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
            $this->log->error("Firefox drivers not loaded (GeckoDriver). Exiting bot.");
            exit();
        } catch (WebDriverCurlException $e) {
            var_dump($e->getMessage());die();
            $this->log->error("Selenium driver not loaded (Did u loaded GeckoDriver?). Details: " . $e->getMessage());
            exit();
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //TODO: revisar si se hq pasado el param debug. si se ha pasado iniciar con debug y sino no. ademas revisar que funcionen las globals
        //TODO: que se manden correpos (ahora mismo no se envía)
        $GLOBALS['debug'] = false;
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
             * Iniciar escuchadores BOT - REST.
             */
            $this->socketCreate();

            /*
             * Iniciar Selenium Driver.
             */
            $this->initSeleniumDriver();

            /*
             * Preparar query para la cola.
             */
            $qb = $this->em->createQueryBuilder();
            $taskQuery = $qb->select(array('q'))
                ->from('App:Queue', 'q')
                ->orderBy('q.id', 'ASC')
                ->setMaxResults(1)
                ->getQuery();

            /*
             * Marcar estado del bot como iniciando.
             */
            $this->bm->setBotStatus("WAITING_TASKS");

            /*
             * Procesar cola.
             */
            while ($this->processQueue) {
                if ($this->bm->getBotStatus() === "SS_PAGE_DOWN") {
                    $this->log->error("Página de la seguridad social inactiva. Esperando " . getenv("SS_PAGE_DOWN_SLEEP") . " segundos.");
                    sleep(getenv("SS_PAGE_DOWN_SLEEP"));
                }
                /*
                 * Recuperar los resultados actuales.
                 */
                $task = $taskQuery->getOneOrNullResult();

                /*
                 * Si hay cosas por hacer, se hacen.
                 * Si no, esperar a que haya algo por sockets.
                 */
                if ($task != null) {
                    $this->bm->setBotStatus("RUNNING");
                    $this->processTask($task);
                } else {
                    $this->bm->setBotStatus("WAITING_TASKS");
                    $this->getContainer()->get("so.commands")->resetNavigator();
                    $this->processQueue = $this->waitForTask();
                }
            }
            $this->socketKill();

        } catch (\Exception $e) {
            var_dump($e->getMessage());die();
            $this->log->error("Ha ocurrido un error interno en el comando de [procesamiento de cola]: " . $e->getMessage());
        }
    }
}