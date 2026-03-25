<?php

namespace App\Tests\Controller;

use App\Tests\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ReportControllerTest extends WebTestCase
{
    use DatabaseTestTrait;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->truncateAll();
    }

    public function testMilkReportLoads(): void
    {
        $this->client->request('GET', '/reports/milk');
        self::assertResponseIsSuccessful();
    }

    public function testFeedReportLoads(): void
    {
        $this->client->request('GET', '/reports/feed');
        self::assertResponseIsSuccessful();
    }

    public function testYoungHighFeedReportLoads(): void
    {
        $this->client->request('GET', '/reports/young-high-feed');
        self::assertResponseIsSuccessful();
    }

    public function testMilkCsvExport(): void
    {
        $farm = $this->createFarm();
        $this->createCow('COW-MILK-CSV', $farm, milk: 150.0);

        $this->client->request('GET', '/reports/milk/csv');
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'text/csv; charset=UTF-8');

        $content = $this->client->getInternalResponse()->getContent();
        self::assertStringContainsString('COW-MILK-CSV', $content);
    }

    public function testFeedCsvExport(): void
    {
        $farm = $this->createFarm();
        $this->createCow('COW-FEED-CSV', $farm, feed: 80.0);

        $this->client->request('GET', '/reports/feed/csv');
        self::assertResponseIsSuccessful();

        $content = $this->client->getInternalResponse()->getContent();
        self::assertStringContainsString('COW-FEED-CSV', $content);
    }

    public function testYoungHighFeedCsvExport(): void
    {
        $farm = $this->createFarm();
        $this->createCow('COW-YOUNG-CSV', $farm, feed: 600.0, birthdate: '-6 months');

        $this->client->request('GET', '/reports/young-high-feed/csv');
        self::assertResponseIsSuccessful();

        $content = $this->client->getInternalResponse()->getContent();
        self::assertStringContainsString('COW-YOUNG-CSV', $content);
    }
}
