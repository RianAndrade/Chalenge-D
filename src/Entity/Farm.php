<?php

namespace App\Entity;

use App\Repository\FarmRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FarmRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'Já existe uma fazenda com este nome.')]
class Farm
{
    public const MAX_ANIMALS_PER_HECTARE = 18;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'O nome é obrigatório.')]
    #[Assert\Length(max: 255, maxMessage: 'O nome deve ter no máximo {{ limit }} caracteres.')]
    private ?string $name = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'O tamanho é obrigatório.')]
    #[Assert\Positive(message: 'O tamanho deve ser maior que zero.')]
    private ?float $size = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'O responsável é obrigatório.')]
    #[Assert\Length(max: 255, maxMessage: 'O responsável deve ter no máximo {{ limit }} caracteres.')]
    private ?string $manager = null;

    /**
     * @var Collection<int, Veterinarian>
     */
    #[ORM\ManyToMany(targetEntity: Veterinarian::class, inversedBy: 'farms')]
    #[ORM\JoinTable(name: 'farm_veterinarian')]
    private Collection $veterinarians;

    /**
     * @var Collection<int, Cow>
     */
    #[ORM\OneToMany(targetEntity: Cow::class, mappedBy: 'farm')]
    private Collection $cows;

    public function __construct()
    {
        $this->veterinarians = new ArrayCollection();
        $this->cows = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSize(): ?float
    {
        return $this->size;
    }

    public function setSize(float $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getManager(): ?string
    {
        return $this->manager;
    }

    public function setManager(string $manager): static
    {
        $this->manager = $manager;

        return $this;
    }

    /**
     * @return Collection<int, Veterinarian>
     */
    public function getVeterinarians(): Collection
    {
        return $this->veterinarians;
    }

    public function addVeterinarian(Veterinarian $veterinarian): static
    {
        if (!$this->veterinarians->contains($veterinarian)) {
            $this->veterinarians->add($veterinarian);
        }

        return $this;
    }

    public function removeVeterinarian(Veterinarian $veterinarian): static
    {
        $this->veterinarians->removeElement($veterinarian);

        return $this;
    }

    /**
     * @return Collection<int, Cow>
     */
    public function getCows(): Collection
    {
        return $this->cows;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
