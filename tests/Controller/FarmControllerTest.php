<?php

namespace App\Tests\Controller;

use App\Tests\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FarmControllerTest extends WebTestCase
{
    use DatabaseTestTrait;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->truncateAll();
    }

    public function testIndex(): void
    {
        $this->client->request('GET', '/farms/');
        self::assertResponseIsSuccessful();
    }

    public function testCreatePageLoads(): void
    {
        $this->client->request('GET', '/farms/create');
        self::assertResponseIsSuccessful();
    }

    public function testCreateSubmit(): void
    {
        $crawler = $this->client->request('GET', '/farms/create');
        $form = $crawler->selectButton('Cadastrar')->form([
            'farm[name]' => 'Fazenda Nova',
            'farm[size]' => '50',
            'farm[manager]' => 'Carlos',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert', 'sucesso');
    }

    public function testCreateValidationError(): void
    {
        $crawler = $this->client->request('GET', '/farms/create');
        $form = $crawler->selectButton('Cadastrar')->form([
            'farm[name]' => '',
            'farm[size]' => '10',
            'farm[manager]' => 'Carlos',
        ]);
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
    }

    public function testShow(): void
    {
        $farm = $this->createFarm('Fazenda Visível');
        $this->client->request('GET', '/farms/' . $farm->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Fazenda Visível');
    }

    public function testEditPageLoads(): void
    {
        $farm = $this->createFarm();
        $this->client->request('GET', '/farms/' . $farm->getId() . '/edit');

        self::assertResponseIsSuccessful();
    }

    public function testEditSubmit(): void
    {
        $farm = $this->createFarm();
        $crawler = $this->client->request('GET', '/farms/' . $farm->getId() . '/edit');
        $form = $crawler->selectButton('Atualizar')->form([
            'farm[name]' => 'Fazenda Atualizada',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert', 'sucesso');
    }

    public function testDeleteWithValidToken(): void
    {
        $farm = $this->createFarm();
        $id = $farm->getId();

        $this->client->request('POST', '/farms/' . $id . '/delete', [
            '_token' => $this->getCsrfToken('delete' . $id),
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert', 'sucesso');
    }

    public function testDeleteWithInvalidToken(): void
    {
        $farm = $this->createFarm();

        $this->client->request('POST', '/farms/' . $farm->getId() . '/delete', [
            '_token' => 'invalid',
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert', 'Token inválido');
    }

    public function testDeleteFarmWithAliveCowsFails(): void
    {
        $farm = $this->createFarm();
        $this->createCow('COW-001', $farm);
        $id = $farm->getId();

        $this->client->request('POST', '/farms/' . $id . '/delete', [
            '_token' => $this->getCsrfToken('delete' . $id),
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert', 'animais vivos');
    }
}
