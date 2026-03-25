<?php

namespace App\Entity;

use App\Repository\CowRepository;
use App\Validator\FarmCapacity;
use App\Validator\UniqueAliveCode;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CowRepository::class)]
#[FarmCapacity]
#[UniqueAliveCode]
class Cow
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'O código é obrigatório.')]
    #[Assert\Length(max: 50, maxMessage: 'O código deve ter no máximo {{ limit }} caracteres.')]
    private ?string $code = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'A produção de leite é obrigatória.')]
    #[Assert\PositiveOrZero(message: 'A produção de leite deve ser zero ou maior.')]
    #[Assert\LessThanOrEqual(value: 10000, message: 'A produção de leite não pode exceder {{ compared_value }} litros.')]
    private ?float $milk = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'A quantidade de ração é obrigatória.')]
    #[Assert\Positive(message: 'A quantidade de ração deve ser maior que zero.')]
    #[Assert\LessThanOrEqual(value: 10000, message: 'A quantidade de ração não pode exceder {{ compared_value }} kg.')]
    private ?float $feed = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'O peso é obrigatório.')]
    #[Assert\Positive(message: 'O peso deve ser maior que zero.')]
    #[Assert\LessThanOrEqual(value: 5000, message: 'O peso não pode exceder {{ compared_value }} kg.')]
    private ?float $weight = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'A data de nascimento é obrigatória.')]
    #[Assert\LessThanOrEqual('today', message: 'A data de nascimento não pode ser no futuro.')]
    private ?\DateTimeInterface $birthdate = null;

    #[ORM\ManyToOne(targetEntity: Farm::class, inversedBy: 'cows')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'A fazenda é obrigatória.')]
    private ?Farm $farm = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $slaughter = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getMilk(): ?float
    {
        return $this->milk;
    }

    public function setMilk(float $milk): static
    {
        $this->milk = $milk;

        return $this;
    }

    public function getFeed(): ?float
    {
        return $this->feed;
    }

    public function setFeed(float $feed): static
    {
        $this->feed = $feed;

        return $this;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(float $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getBirthdate(): ?\DateTimeInterface
    {
        return $this->birthdate;
    }

    public function setBirthdate(\DateTimeInterface $birthdate): static
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    public function getFarm(): ?Farm
    {
        return $this->farm;
    }

    public function setFarm(?Farm $farm): static
    {
        $this->farm = $farm;

        return $this;
    }

    public function getSlaughter(): ?\DateTimeInterface
    {
        return $this->slaughter;
    }

    public function setSlaughter(?\DateTimeInterface $slaughter): static
    {
        $this->slaughter = $slaughter;

        return $this;
    }

    public function isAlive(): bool
    {
        return $this->slaughter === null;
    }

    public function getAgeInYears(): float
    {
        if (!$this->birthdate) {
            return 0;
        }

        $diff = $this->birthdate->diff(new \DateTime());

        return $diff->y + $diff->m / 12 + $diff->d / 365.25;
    }

    public function getDailyFeed(): float
    {
        return $this->feed ? $this->feed / 7 : 0;
    }

    public function getWeightInArrobas(): float
    {
        return $this->weight ? $this->weight / 15 : 0;
    }

    public function isEligibleForSlaughter(): bool
    {
        if (!$this->isAlive()) {
            return false;
        }

        if ($this->getAgeInYears() > 5) {
            return true;
        }

        if ($this->milk !== null && $this->milk < 40) {
            return true;
        }

        if ($this->milk !== null && $this->milk < 70 && $this->getDailyFeed() > 50) {
            return true;
        }

        if ($this->getWeightInArrobas() > 18) {
            return true;
        }

        return false;
    }

    public function getSlaughterReasons(): array
    {
        $reasons = [];

        if ($this->getAgeInYears() > 5) {
            $reasons[] = 'Mais de 5 anos de idade';
        }

        if ($this->milk !== null && $this->milk < 40) {
            $reasons[] = 'Produz menos de 40 litros de leite por semana';
        }

        if ($this->milk !== null && $this->milk < 70 && $this->getDailyFeed() > 50) {
            $reasons[] = 'Produz menos de 70L/sem e consome mais de 50kg de ração/dia';
        }

        if ($this->getWeightInArrobas() > 18) {
            $reasons[] = 'Peso maior que 18 arrobas';
        }

        return $reasons;
    }
}
