<?php

namespace App\Tests\Validator;

use App\Entity\Cow;
use App\Tests\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FarmCapacityValidatorTest extends KernelTestCase
{
    use DatabaseTestTrait;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->truncateAll();
        $this->validator = static::getContainer()->get('validator');
    }

    public function testCowAllowedWhenFarmHasCapacity(): void
    {
        $farm = $this->createFarm('Fazenda A', 1.0);

        for ($i = 1; $i <= 17; $i++) {
            $this->createCow(sprintf('COW-%03d', $i), $farm);
        }

        $cow = new Cow();
        $cow->setCode('COW-NEW');
        $cow->setMilk(100.0);
        $cow->setFeed(50.0);
        $cow->setWeight(200.0);
        $cow->setBirthdate(new \DateTime('-2 years'));
        $cow->setFarm($farm);

        $violations = $this->validator->validate($cow);

        $farmViolations = [];
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'farm') {
                $farmViolations[] = $violation;
            }
        }

        $this->assertCount(0, $farmViolations);
    }

    public function testCowRejectedWhenFarmAtCapacity(): void
    {
        $farm = $this->createFarm('Fazenda Cheia', 1.0);

        for ($i = 1; $i <= 18; $i++) {
            $this->createCow(sprintf('COW-%03d', $i), $farm);
        }

        $cow = new Cow();
        $cow->setCode('COW-NEW');
        $cow->setMilk(100.0);
        $cow->setFeed(50.0);
        $cow->setWeight(200.0);
        $cow->setBirthdate(new \DateTime('-2 years'));
        $cow->setFarm($farm);

        $violations = $this->validator->validate($cow);

        $farmViolations = [];
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'farm') {
                $farmViolations[] = $violation;
            }
        }

        $this->assertCount(1, $farmViolations);
        $this->assertStringContainsString('Fazenda Cheia', $farmViolations[0]->getMessage());
    }

    public function testSlaughteredCowsDoNotCountTowardCapacity(): void
    {
        $farm = $this->createFarm('Fazenda B', 1.0);

        for ($i = 1; $i <= 17; $i++) {
            $this->createCow(sprintf('ALIVE-%03d', $i), $farm);
        }

        for ($i = 1; $i <= 5; $i++) {
            $this->createCow(sprintf('DEAD-%03d', $i), $farm, slaughtered: true);
        }

        $cow = new Cow();
        $cow->setCode('COW-NEW');
        $cow->setMilk(100.0);
        $cow->setFeed(50.0);
        $cow->setWeight(200.0);
        $cow->setBirthdate(new \DateTime('-2 years'));
        $cow->setFarm($farm);

        $violations = $this->validator->validate($cow);

        $farmViolations = [];
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'farm') {
                $farmViolations[] = $violation;
            }
        }

        $this->assertCount(0, $farmViolations);
    }

    public function testEditingExistingAliveCowDoesNotDoubleCount(): void
    {
        $farm = $this->createFarm('Fazenda C', 1.0);

        $cows = [];
        for ($i = 1; $i <= 18; $i++) {
            $cows[] = $this->createCow(sprintf('COW-%03d', $i), $farm);
        }

        $existingCow = $cows[0];
        $existingCow->setMilk(999.0);

        $violations = $this->validator->validate($existingCow);

        $farmViolations = [];
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'farm') {
                $farmViolations[] = $violation;
            }
        }

        $this->assertCount(0, $farmViolations);
    }

    public function testCowWithNoFarmSkipsValidation(): void
    {
        $cow = new Cow();
        $cow->setCode('COW-ORPHAN');
        $cow->setMilk(100.0);
        $cow->setFeed(50.0);
        $cow->setWeight(200.0);
        $cow->setBirthdate(new \DateTime('-2 years'));

        $violations = $this->validator->validate($cow);

        $capacityPattern = '/atingiu o limite/';
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'farm') {
                $this->assertDoesNotMatchRegularExpression(
                    $capacityPattern,
                    $violation->getMessage(),
                    'FarmCapacity violation should not be present when farm is null.',
                );
            }
        }
    }
}
