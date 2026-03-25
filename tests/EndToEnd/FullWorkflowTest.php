<?php

namespace App\Tests\EndToEnd;

use App\Entity\Cow;
use App\Entity\Farm;
use App\Tests\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FullWorkflowTest extends WebTestCase
{
    use DatabaseTestTrait;

    public function testCrudAndSlaughterWorkflow(): void
    {
        $client = static::createClient();
        $this->truncateAll();
        $em = $this->getEntityManager();

        // Criar fazenda
        $crawler = $client->request('GET', '/farms/create');
        $client->submit($crawler->selectButton('Cadastrar')->form([
            'farm[name]' => 'Fazenda E2E', 'farm[size]' => '10', 'farm[manager]' => 'Gerente',
        ]));
        $client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'sucesso');
        $farmId = $em->getRepository(Farm::class)->findOneBy(['name' => 'Fazenda E2E'])->getId();

        // Criar vaca elegível para abate (milk < 40)
        $crawler = $client->request('GET', '/cows/create');
        $client->submit($crawler->selectButton('Cadastrar')->form([
            'cow[code]' => 'E2E-001', 'cow[milk]' => '30', 'cow[feed]' => '50',
            'cow[weight]' => '200', 'cow[birthdate]' => '2024-01-15', 'cow[farm]' => $farmId,
        ]));
        $client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'sucesso');
        $cowId = $em->getRepository(Cow::class)->findOneBy(['code' => 'E2E-001'])->getId();

        // Vaca aparece no relatório de leite e na lista de abate
        $client->request('GET', '/reports/milk');
        self::assertSelectorTextContains('body', 'E2E-001');

        $crawler = $client->request('GET', '/cows/slaughter/list');
        self::assertSelectorTextContains('body', 'E2E-001');

        // Abater
        $token = $crawler->filter('form[action="/cows/' . $cowId . '/slaughter"] input[name="_token"]')->attr('value');
        $client->request('POST', '/cows/' . $cowId . '/slaughter', ['_token' => $token]);
        $client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'sucesso');

        // Abatida some do leite, aparece no relatório de abate
        $client->request('GET', '/reports/milk');
        self::assertStringNotContainsString('E2E-001', $client->getResponse()->getContent());

        $crawler = $client->request('GET', '/cows/slaughter/report');
        self::assertSelectorTextContains('body', 'E2E-001');

        // Reverter abate
        $em->clear();
        $token = $crawler->filter('form[action="/cows/' . $cowId . '/revert-slaughter"] input[name="_token"]')->attr('value');
        $client->request('POST', '/cows/' . $cowId . '/revert-slaughter', ['_token' => $token]);
        $client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'sucesso');

        // Deletar vaca, depois fazenda
        $crawler = $client->request('GET', '/cows/');
        $token = $crawler->filter('form[action="/cows/' . $cowId . '/delete"] input[name="_token"]')->attr('value');
        $client->request('POST', '/cows/' . $cowId . '/delete', ['_token' => $token]);
        $client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'sucesso');

        $crawler = $client->request('GET', '/farms/');
        $token = $crawler->filter('form[action="/farms/' . $farmId . '/delete"] input[name="_token"]')->attr('value');
        $client->request('POST', '/farms/' . $farmId . '/delete', ['_token' => $token]);
        $client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'sucesso');
    }
}
