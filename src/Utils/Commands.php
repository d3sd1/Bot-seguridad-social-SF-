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
}