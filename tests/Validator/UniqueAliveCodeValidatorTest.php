<?php

namespace App\Tests\Validator;

use App\Entity\Cow;
use App\Tests\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UniqueAliveCodeValidatorTest extends KernelTestCase
{
    use DatabaseTestTrait;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->truncateAll();
        $this->validator = static::getContainer()->get('validator');
    }

    public function testUniqueCodePasses(): void
    {
        $farm = $this->createFarm();
        $this->createCow('A', $farm);

        $cow = new Cow();
        $cow->setCode('B');
        $cow->setMilk(100.0);
        $cow->setFeed(50.0);
        $cow->setWeight(200.0);
        $cow->setBirthdate(new \DateTime('-2 years'));
        $cow->setFarm($farm);

        $violations = $this->validator->validate($cow);

        $codeViolations = [];
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'code') {
                $codeViolations[] = $violation;
            }
        }

        $this->assertCount(0, $codeViolations);
    }

    public function testDuplicateAliveCodeFails(): void
    {
        $farm = $this->createFarm();
        $this->createCow('A', $farm);

        $cow = new Cow();
        $cow->setCode('A');
        $cow->setMilk(100.0);
        $cow->setFeed(50.0);
        $cow->setWeight(200.0);
        $cow->setBirthdate(new \DateTime('-2 years'));
        $cow->setFarm($farm);

        $violations = $this->validator->validate($cow);

        $codeViolations = [];
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'code') {
                $codeViolations[] = $violation;
            }
        }

        $this->assertCount(1, $codeViolations);
        $this->assertStringContainsString('A', $codeViolations[0]->getMessage());
    }

    public function testSameCodeAllowedIfExistingIsSlaughtered(): void
    {
        $farm = $this->createFarm();
        $this->createCow('A', $farm, slaughtered: true);

        $cow = new Cow();
        $cow->setCode('A');
        $cow->setMilk(100.0);
        $cow->setFeed(50.0);
        $cow->setWeight(200.0);
        $cow->setBirthdate(new \DateTime('-2 years'));
        $cow->setFarm($farm);

        $violations = $this->validator->validate($cow);

        $codeViolations = [];
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'code') {
                $codeViolations[] = $violation;
            }
        }

        $this->assertCount(0, $codeViolations);
    }

    public function testEditingOwnCodeDoesNotFail(): void
    {
        $farm = $this->createFarm();
        $cow = $this->createCow('A', $farm);

        $violations = $this->validator->validate($cow);

        $codeViolations = [];
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'code') {
                $codeViolations[] = $violation;
            }
        }

        $this->assertCount(0, $codeViolations);
    }

    public function testNullCodeSkipsValidation(): void
    {
        $cow = new Cow();
        $cow->setMilk(100.0);
        $cow->setFeed(50.0);
        $cow->setWeight(200.0);
        $cow->setBirthdate(new \DateTime('-2 years'));
        $cow->setFarm($this->createFarm());

        $violations = $this->validator->validate($cow);

        $uniqueCodePattern = '/animal vivo com o código/';
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'code') {
                $this->assertDoesNotMatchRegularExpression(
                    $uniqueCodePattern,
                    $violation->getMessage(),
                    'UniqueAliveCode violation should not be present when code is null.',
                );
            }
        }
    }
}
