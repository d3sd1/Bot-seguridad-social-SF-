<?php
namespace App\Command;
set_time_limit(0);

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use App\Utils\Commands;
use App\Utils\CommandUtils;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Firefox\FirefoxProfile;
use Facebook\WebDriver\Firefox\FirefoxDriver;

class DebugBot extends ContainerAwareCommand
{

    private $processQueue = true;
    private $em;

    protected function configure()
    {
        $this
            ->setName("debug:start-bot")
            ->setDescription('Starts the bot on debug mode.')
            ->setHelp('This command allows you to start the bot server, and the required automatizing scripts. You have to get Firefox installed and the bot running under nginx.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $host = 'http://127.0.0.1:4444/wd/hub';
        $capabilities = DesiredCapabilities::chrome();
        $driver = RemoteWebDriver::create($host, $capabilities);

        $driver->get('http://google.com');
        $element = $driver->findElement(WebDriverBy::name('q'));
        $element->sendKeys('polla');
        $element->submit();
    }
}