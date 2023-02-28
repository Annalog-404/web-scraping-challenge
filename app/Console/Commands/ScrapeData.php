<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

class ScrapeData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $host = 'http://localhost:4444';
        $website = 'https://www.melbourne.vic.gov.au/building-and-development/property-information/planning-building-registers/pages/town-planning-permits-register.aspx';

        $options = new ChromeOptions();
        $options->addArguments(['--headless', '--no-sandbox','--window-size=1920,1080']);
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        $driver = RemoteWebDriver::create($host, $capabilities);

        $driver->get($website);

        $dateRange_btn = $driver->findElement(WebDriverBy::linkText('DATE RANGE'));
        $dateRange_btn->click();

        $fromDate_element = $driver->findElement(WebDriverBy::id('ctl00_ctl59_g_283dc50f_625f_49b8_bb9f_204344be6268_txtFromApplicationDate'));
        $toDate_element = $driver->findElement(WebDriverBy::id('ctl00_ctl59_g_283dc50f_625f_49b8_bb9f_204344be6268_txtToApplicationDate'));
        $fromDate_element->sendkeys('09/02/2023');
        $toDate_element->sendkeys('23/02/2023');

        $search_btn = $driver->findElement(WebDriverBy::id('ctl00_ctl59_g_283dc50f_625f_49b8_bb9f_204344be6268_btniCompasSearchDateRange'));
        $search_btn->click();

        $max_pages = 5;
        $current_page = 1;
        $planningPermitData = [];

        $updated_url = $driver->getCurrentURL();

        while($current_page <= $max_pages)
        {
            $current_url = $updated_url.'&page='.$current_page;
            
            $application_el = $driver->findElements(WebDriverBy::xpath('//*[@id="ctl00_ctl59_g_32449bb4_2103_48cc_ac44_8e240d2b040d"]/div/table/tbody/tr/td[1]/a'));
            $received_el = $driver->findElements(WebDriverBy::xpath('//*[@id="ctl00_ctl59_g_32449bb4_2103_48cc_ac44_8e240d2b040d"]/div/table/tbody/tr/td[2]'));
            $address_el = $driver->findElements(WebDriverBy::xpath('//*[@id="ctl00_ctl59_g_32449bb4_2103_48cc_ac44_8e240d2b040d"]/div/table/tbody/tr/td[3]'));
            $proposal_el = $driver->findElements(WebDriverBy::xpath('//*[@id="ctl00_ctl59_g_32449bb4_2103_48cc_ac44_8e240d2b040d"]/div/table/tbody/tr/td[4]/div'));
            $status_el = $driver->findElements(WebDriverBy::xpath('//*[@id="ctl00_ctl59_g_32449bb4_2103_48cc_ac44_8e240d2b040d"]/div/table/tbody/tr/td[5]'));

            foreach (range(0, 9) as $i) {
                $temp_data = json_encode([
                    'Application' => $application_el[$i]->getText(),
                    'Recieved' => $received_el[$i]->getText(),
                    'Address' => $address_el[$i]->getText(),
                    'Proposal' => $proposal_el[$i]->getText(),
                    'Status' => $status_el[$i]->getText()
                ]);

                array_push($planningPermitData, $temp_data);
            }

            sleep(10);
            $current_page += 1;
        }

        print_r($planningPermitData);

        $driver->quit();

        return 0;
    }

}
