<?php

namespace App\Tests\Entity;

use App\Entity\Cow;
use App\Entity\Farm;
use PHPUnit\Framework\TestCase;

class CowTest extends TestCase
{
    private function createCow(
        float $milk = 100,
        float $feed = 200,
        float $weight = 150,
        string $birthdate = '-2 years',
        ?\DateTimeInterface $slaughter = null,
    ): Cow {
        $cow = new Cow();
        $cow->setCode('TEST-001');
        $cow->setMilk($milk);
        $cow->setFeed($feed);
        $cow->setWeight($weight);
        $cow->setBirthdate(new \DateTime($birthdate));
        $cow->setFarm((new Farm())->setName('Fazenda Teste')->setSize(10)->setManager('João'));

        if ($slaughter) {
            $cow->setSlaughter($slaughter);
        }

        return $cow;
    }

    public function testIsAliveWhenNoSlaughterDate(): void
    {
        $cow = $this->createCow();
        $this->assertTrue($cow->isAlive());
    }

    public function testIsNotAliveWhenSlaughtered(): void
    {
        $cow = $this->createCow(slaughter: new \DateTime());
        $this->assertFalse($cow->isAlive());
    }

    public function testAgeInYearsWithNullBirthdate(): void
    {
        $cow = new Cow();
        $this->assertSame(0.0, $cow->getAgeInYears());
    }

    public function testAgeInYearsWithRecentBirthdate(): void
    {
        $cow = $this->createCow(birthdate: '-6 months');
        $this->assertLessThan(1.0, $cow->getAgeInYears());
        $this->assertGreaterThan(0.0, $cow->getAgeInYears());
    }

    public function testAgeInYearsWithOldBirthdate(): void
    {
        $cow = $this->createCow(birthdate: '-6 years');
        $this->assertGreaterThan(5.0, $cow->getAgeInYears());
    }

    public function testDailyFeedCalculation(): void
    {
        $cow = $this->createCow(feed: 700);
        $this->assertEqualsWithDelta(100.0, $cow->getDailyFeed(), 0.01);
    }

    public function testDailyFeedWithZeroFeed(): void
    {
        $cow = new Cow();
        $this->assertSame(0.0, $cow->getDailyFeed());
    }

    public function testWeightInArrobasCalculation(): void
    {
        $cow = $this->createCow(weight: 270);
        $this->assertEqualsWithDelta(18.0, $cow->getWeightInArrobas(), 0.01);
    }

    public function testWeightInArrobasWithZeroWeight(): void
    {
        $cow = new Cow();
        $this->assertSame(0.0, $cow->getWeightInArrobas());
    }

    // --- isEligibleForSlaughter edge cases ---

    public function testNotEligibleWhenSlaughtered(): void
    {
        $cow = $this->createCow(milk: 10, slaughter: new \DateTime());
        $this->assertFalse($cow->isEligibleForSlaughter());
    }

    public function testEligibleWhenOlderThanFiveYears(): void
    {
        $cow = $this->createCow(birthdate: '-6 years');
        $this->assertTrue($cow->isEligibleForSlaughter());
    }

    public function testNotEligibleAtExactlyFiveYears(): void
    {
        $cow = $this->createCow(birthdate: '-5 years');
        // 5 years + 0 months = 5.0, not > 5
        $this->assertFalse($cow->isEligibleForSlaughter());
    }

    public function testEligibleWhenMilkBelow40(): void
    {
        $cow = $this->createCow(milk: 39.99);
        $this->assertTrue($cow->isEligibleForSlaughter());
    }

    public function testNotEligibleWhenMilkExactly40(): void
    {
        $cow = $this->createCow(milk: 40, feed: 100, weight: 100);
        $this->assertFalse($cow->isEligibleForSlaughter());
    }

    public function testEligibleWhenMilkBelow70AndDailyFeedAbove50(): void
    {
        // feed = 351 → daily = 50.14 > 50
        $cow = $this->createCow(milk: 69, feed: 351, weight: 100);
        $this->assertTrue($cow->isEligibleForSlaughter());
    }

    public function testNotEligibleWhenMilkBelow70ButDailyFeedExactly50(): void
    {
        // feed = 350 → daily = 50.0, not > 50
        $cow = $this->createCow(milk: 69, feed: 350, weight: 100);
        $this->assertFalse($cow->isEligibleForSlaughter());
    }

    public function testNotEligibleWhenMilkExactly70AndHighFeed(): void
    {
        // milk = 70, not < 70 so this rule doesn't apply
        $cow = $this->createCow(milk: 70, feed: 500, weight: 100);
        $this->assertFalse($cow->isEligibleForSlaughter());
    }

    public function testEligibleWhenWeightAbove18Arrobas(): void
    {
        // 271 / 15 = 18.066 > 18
        $cow = $this->createCow(milk: 100, feed: 100, weight: 271);
        $this->assertTrue($cow->isEligibleForSlaughter());
    }

    public function testNotEligibleWhenWeightExactly18Arrobas(): void
    {
        // 270 / 15 = 18.0, not > 18
        $cow = $this->createCow(milk: 100, feed: 100, weight: 270);
        $this->assertFalse($cow->isEligibleForSlaughter());
    }

    public function testHealthyCowNotEligible(): void
    {
        // milk >= 70, daily feed <= 50, weight <= 18 arrobas, age <= 5
        $cow = $this->createCow(milk: 100, feed: 200, weight: 200, birthdate: '-2 years');
        $this->assertFalse($cow->isEligibleForSlaughter());
    }

    // --- getSlaughterReasons edge cases ---

    public function testSlaughterReasonsMultipleReasons(): void
    {
        // old (>5y), low milk (<40), high feed (daily>50), heavy (>18@)
        $cow = $this->createCow(milk: 30, feed: 400, weight: 300, birthdate: '-6 years');
        $reasons = $cow->getSlaughterReasons();
        $this->assertCount(4, $reasons);
    }

    public function testSlaughterReasonsEmptyForHealthyCow(): void
    {
        $cow = $this->createCow(milk: 100, feed: 200, weight: 200, birthdate: '-2 years');
        $this->assertEmpty($cow->getSlaughterReasons());
    }

    public function testZeroMilkIsEligible(): void
    {
        $cow = $this->createCow(milk: 0);
        $this->assertTrue($cow->isEligibleForSlaughter());
    }

    // --- Entity relationship edge cases ---

    public function testCowWithoutFarmReturnsNull(): void
    {
        $cow = new Cow();
        $this->assertNull($cow->getFarm());
    }

    public function testSettersReturnSelf(): void
    {
        $cow = new Cow();
        $this->assertSame($cow, $cow->setCode('X'));
        $this->assertSame($cow, $cow->setMilk(0));
        $this->assertSame($cow, $cow->setFeed(1));
        $this->assertSame($cow, $cow->setWeight(1));
        $this->assertSame($cow, $cow->setBirthdate(new \DateTime()));
        $this->assertSame($cow, $cow->setFarm(new Farm()));
        $this->assertSame($cow, $cow->setSlaughter(new \DateTime()));
    }
}
