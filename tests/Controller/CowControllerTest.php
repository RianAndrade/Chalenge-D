<?php

namespace App\Tests\Controller;

use App\Entity\Cow;
use App\Tests\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CowControllerTest extends WebTestCase
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
        $this->client->request('GET', '/cows/');
        self::assertResponseIsSuccessful();
    }

    public function testCreatePageLoads(): void
    {
        $this->client->request('GET', '/cows/create');
        self::assertResponseIsSuccessful();
    }

    public function testCreateSubmit(): void
    {
        $farm = $this->createFarm();
        $crawler = $this->client->request('GET', '/cows/create');
        $form = $crawler->selectButton('Cadastrar')->form([
            'cow[code]' => 'COW-NEW',
            'cow[milk]' => '80',
            'cow[feed]' => '40',
            'cow[weight]' => '300',
            'cow[birthdate]' => '2024-01-15',
            'cow[farm]' => $farm->getId(),
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert', 'sucesso');
    }

    public function testShow(): void
    {
        $farm = $this->createFarm();
        $cow = $this->createCow('COW-SHOW', $farm);
        $this->client->request('GET', '/cows/' . $cow->getId());

        self::assertResponseIsSuccessful();
    }

    public function testEdit(): void
    {
        $farm = $this->createFarm();
        $cow = $this->createCow('COW-EDIT', $farm);
        $crawler = $this->client->request('GET', '/cows/' . $cow->getId() . '/edit');
        $form = $crawler->selectButton('Atualizar')->form([
            'cow[milk]' => '150',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert', 'sucesso');
    }

    public function testDeleteWithValidToken(): void
    {
        $farm = $this->createFarm();
        $cow = $this->createCow('COW-DEL', $farm);
        $id = $cow->getId();

        $this->client->request('POST', '/cows/' . $id . '/delete', [
            '_token' => $this->getCsrfToken('delete' . $id),
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert', 'sucesso');
    }

    public function testDeleteWithInvalidToken(): void
    {
        $farm = $this->createFarm();
        $cow = $this->createCow('COW-BAD', $farm);

        $this->client->request('POST', '/cows/' . $cow->getId() . '/delete', [
            '_token' => 'invalid',
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert', 'Token inválido');
    }

    public function testSlaughterListLoads(): void
    {
        $farm = $this->createFarm();
        $this->createCow('COW-SL', $farm, milk: 30.0);
        $this->client->request('GET', '/cows/slaughter/list');

        self::assertResponseIsSuccessful();
    }

    public function testSlaughterReportLoads(): void
    {
        $farm = $this->createFarm();
        $this->createCow('COW-SR', $farm, slaughtered: true);
        $this->client->request('GET', '/cows/slaughter/report');

        self::assertResponseIsSuccessful();
    }

    public function testSlaughterAction(): void
    {
        $farm = $this->createFarm();
        $cow = $this->createCow('COW-SLAUGHT', $farm, milk: 30.0);
        $id = $cow->getId();

        $this->client->request('POST', '/cows/' . $id . '/slaughter', [
            '_token' => $this->getCsrfToken('slaughter' . $id),
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert', 'sucesso');

        $this->getEntityManager()->clear();
        $updated = $this->getEntityManager()->getRepository(Cow::class)->find($id);
        self::assertNotNull($updated->getSlaughter());
    }

    public function testSlaughterAlreadySlaughteredCow(): void
    {
        $farm = $this->createFarm();
        $cow = $this->createCow('COW-ALREADY', $farm, milk: 30.0, slaughtered: true);
        $id = $cow->getId();

        $this->client->request('POST', '/cows/' . $id . '/slaughter', [
            '_token' => $this->getCsrfToken('slaughter' . $id),
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert', 'já foi abatido');
    }

    public function testRevertSlaughter(): void
    {
        $farm = $this->createFarm();
        $cow = $this->createCow('COW-REVERT', $farm, slaughtered: true);
        $id = $cow->getId();

        $this->client->request('POST', '/cows/' . $id . '/revert-slaughter', [
            '_token' => $this->getCsrfToken('revert_slaughter' . $id),
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert', 'sucesso');
    }

    public function testRevertSlaughterAlreadyAlive(): void
    {
        $farm = $this->createFarm();
        $cow = $this->createCow('COW-ALIVE', $farm);
        $id = $cow->getId();

        $this->client->request('POST', '/cows/' . $id . '/revert-slaughter', [
            '_token' => $this->getCsrfToken('revert_slaughter' . $id),
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert', 'não está abatido');
    }
}
