<?php

namespace App\Tests\Entity;

use App\Entity\Veterinarian;
use PHPUnit\Framework\TestCase;

class VeterinarianTest extends TestCase
{
    public function testNewVeterinarianHasEmptyFarms(): void
    {
        $vet = new Veterinarian();
        $this->assertCount(0, $vet->getFarms());
    }

    public function testToStringWithNullName(): void
    {
        $vet = new Veterinarian();
        $this->assertSame('', (string) $vet);
    }

    public function testToStringWithName(): void
    {
        $vet = new Veterinarian();
        $vet->setName('Dr. Maria');
        $this->assertSame('Dr. Maria', (string) $vet);
    }

    public function testSettersReturnSelf(): void
    {
        $vet = new Veterinarian();
        $this->assertSame($vet, $vet->setName('Test'));
        $this->assertSame($vet, $vet->setCrmv('12345'));
    }

    public function testNewVeterinarianIdIsNull(): void
    {
        $vet = new Veterinarian();
        $this->assertNull($vet->getId());
    }
}
