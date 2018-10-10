<?php
namespace App\Command;
set_time_limit(0);

use App\Utils\Commands;
use App\Utils\CommandUtils;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Firefox\FirefoxProfile;
use Facebook\WebDriver\Firefox\FirefoxDriver;

class DebugServer extends ContainerAwareCommand
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
        // start Chrome with 5 second timeout
        $host = 'http://localhost:4444/wd/hub'; // this is the default
        $capabilities = DesiredCapabilities::chrome();
        $driver = RemoteWebDriver::create($host, $capabilities, 5000);
// navigate to 'http://www.seleniumhq.org/'
        $driver->get('https://www.seleniumhq.org/');
// adding cookie
        $driver->manage()->deleteAllCookies();
        $cookie = new Cookie('cookie_name', 'cookie_value');
        $driver->manage()->addCookie($cookie);
        $cookies = $driver->manage()->getCookies();
        print_r($cookies);
// click the link 'About'
        $link = $driver->findElement(
            WebDriverBy::id('menu_about')
        );
        $link->click();
// wait until the page is loaded
        $driver->wait()->until(
            WebDriverExpectedCondition::titleContains('About')
        );
// print the title of the current page
        echo "The title is '" . $driver->getTitle() . "'\n";
// print the URI of the current page
        echo "The current URI is '" . $driver->getCurrentURL() . "'\n";
// write 'php' in the search box
        $driver->findElement(WebDriverBy::id('q'))
            ->sendKeys('php') // fill the search box
            ->submit(); // submit the whole form
// wait at most 10 seconds until at least one result is shown
        $driver->wait(10)->until(
            WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(
                WebDriverBy::className('gsc-result')
            )
        );
// close the browser
        $driver->quit();
    }
}