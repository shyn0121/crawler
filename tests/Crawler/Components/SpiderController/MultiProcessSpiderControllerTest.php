<?php

namespace Tests\Crawler\Components\SpiderController;

use Tests\TestCase;

class MultiSpiderControllerTest extends TestCase
{
    public function testStart()
    {
        $this->container->make('Config')['startLink'] = 'https://www.baidu.com';

        $spiderController = $this->container->make("SpiderController");

        $spiderController->start();
    }
}