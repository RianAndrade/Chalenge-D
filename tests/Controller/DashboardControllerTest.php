<?php

namespace App\Tests\Controller;

use App\Tests\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardControllerTest extends WebTestCase
{
    use DatabaseTestTrait;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->truncateAll();
    }

    public function testDashboardLoads(): void
    {
        $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();
    }

    public function testDashboardShowsStatistics(): void
    {
        $farm = $this->createFarm();
        $this->createCow('COW-A', $farm, milk: 120.0, feed: 60.0);
        $this->createCow('COW-B', $farm, milk: 80.0, feed: 40.0);

        $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();

        $content = $this->client->getResponse()->getContent();
        self::assertStringContainsString('200', $content);
        self::assertStringContainsString('100', $content);
    }
}
