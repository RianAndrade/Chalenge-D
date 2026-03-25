<?php

namespace App\Tests\Entity;

use App\Entity\Cow;
use App\Entity\Farm;
use App\Entity\Veterinarian;
use PHPUnit\Framework\TestCase;

class FarmTest extends TestCase
{
    public function testMaxAnimalsPerHectareConstant(): void
    {
        $this->assertSame(18, Farm::MAX_ANIMALS_PER_HECTARE);
    }

    public function testNewFarmHasEmptyCollections(): void
    {
        $farm = new Farm();
        $this->assertCount(0, $farm->getCows());
        $this->assertCount(0, $farm->getVeterinarians());
    }

    public function testAddDuplicateVeterinarianDoesNotDuplicate(): void
    {
        $farm = new Farm();
        $vet = new Veterinarian();
        $vet->setName('Dr. Test')->setCrmv('12345');

        $farm->addVeterinarian($vet);
        $farm->addVeterinarian($vet);

        $this->assertCount(1, $farm->getVeterinarians());
    }

    public function testRemoveVeterinarianThatDoesNotExist(): void
    {
        $farm = new Farm();
        $vet = new Veterinarian();
        $vet->setName('Dr. Test')->setCrmv('12345');

        $farm->removeVeterinarian($vet);
        $this->assertCount(0, $farm->getVeterinarians());
    }

    public function testToStringWithNullName(): void
    {
        $farm = new Farm();
        $this->assertSame('', (string) $farm);
    }

    public function testToStringWithName(): void
    {
        $farm = new Farm();
        $farm->setName('Fazenda Sol');
        $this->assertSame('Fazenda Sol', (string) $farm);
    }

    public function testSettersReturnSelf(): void
    {
        $farm = new Farm();
        $this->assertSame($farm, $farm->setName('Test'));
        $this->assertSame($farm, $farm->setSize(10.0));
        $this->assertSame($farm, $farm->setManager('João'));
    }

    public function testNewFarmIdIsNull(): void
    {
        $farm = new Farm();
        $this->assertNull($farm->getId());
    }
}
