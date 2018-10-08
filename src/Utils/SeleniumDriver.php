<?php

namespace App\Utils;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Firefox\FirefoxProfile;
use Facebook\WebDriver\Firefox\FirefoxDriver;
/*
 * Clase para la carga del controlador Selenium
 */

class SeleniumDriver
{
    private $driver;
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        /*
         * CARGAR CONTROLADOR
         */
        try{
            $caps = DesiredCapabilities::firefox();
            /*
             * Si se requiere de cambiar el certificado, simplemente cambiar el perfil de firefox.
             * Para ello, crear un perfil y exportarlo a zip y base 64.
             */
            $caps->setCapability(FirefoxDriver::PROFILE, file_get_contents('/var/www/drivers/profile.zip.b64'));
            $caps->setPlatform("Linux");
            $host = 'http://localhost:4444/wd/hub/';

            $this->driver = RemoteWebDriver::create($host, $caps);
        }
        catch(\Facebook\WebDriver\Exception\SessionNotCreatedException $e)
        {
            $this->container->get("app.dblogger")->error("Firefox drivers not loaded. Exiting bot.");
            exit();
        }
        catch(\Facebook\WebDriver\Exception\WebDriverCurlException $e)
        {
            var_dump($e->getMessage());
            $this->container->get("app.dblogger")->error("Selenium driver not loaded (Did u loaded GeckoDriver?). Details: ". $e->getMessage());
            exit();
        }
    }

    /**
     * @return mixed
     */
    public function getDriver(): RemoteWebDriver
    {
        return $this->driver;
    }
}