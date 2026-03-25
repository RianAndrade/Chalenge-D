<?php

namespace App\Tests\Controller;

use App\Entity\Veterinarian;
use App\Tests\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class VeterinarianControllerTest extends WebTestCase
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
        $this->client->request('GET', '/veterinarians/');
        self::assertResponseIsSuccessful();
    }

    public function testCreateAndShow(): void
    {
        $crawler = $this->client->request('GET', '/veterinarians/create');
        $form = $crawler->selectButton('Cadastrar')->form([
            'veterinarian[name]' => 'Dr. Novo',
            'veterinarian[crmv]' => 'CRMV-999',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert', 'sucesso');

        $vet = $this->getEntityManager()->getRepository(Veterinarian::class)->findOneBy(['crmv' => 'CRMV-999']);
        $this->client->request('GET', '/veterinarians/' . $vet->getId());
        self::assertResponseIsSuccessful();
    }

    public function testEdit(): void
    {
        $vet = $this->createVeterinarian();
        $crawler = $this->client->request('GET', '/veterinarians/' . $vet->getId() . '/edit');
        $form = $crawler->selectButton('Atualizar')->form([
            'veterinarian[name]' => 'Dr. Atualizado',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert', 'sucesso');
    }

    public function testDeleteWithValidToken(): void
    {
        $vet = $this->createVeterinarian();
        $id = $vet->getId();

        $this->client->request('POST', '/veterinarians/' . $id . '/delete', [
            '_token' => $this->getCsrfToken('delete' . $id),
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert', 'sucesso');

        $removed = $this->getEntityManager()->getRepository(Veterinarian::class)->find($id);
        self::assertNull($removed);
    }

    public function testDeleteWithInvalidToken(): void
    {
        $vet = $this->createVeterinarian();

        $this->client->request('POST', '/veterinarians/' . $vet->getId() . '/delete', [
            '_token' => 'invalid',
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert', 'Token inválido');
    }
}
