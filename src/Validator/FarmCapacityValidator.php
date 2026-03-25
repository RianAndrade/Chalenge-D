<?php

namespace App\Validator;

use App\Entity\Cow;
use App\Entity\Farm;
use App\Repository\CowRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class FarmCapacityValidator extends ConstraintValidator
{
    public function __construct(
        private CowRepository $cowRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof FarmCapacity) {
            throw new UnexpectedTypeException($constraint, FarmCapacity::class);
        }

        if (!$value instanceof Cow) {
            throw new UnexpectedValueException($value, Cow::class);
        }

        $farm = $value->getFarm();

        if (!$farm) {
            return;
        }

        $limit = (int) floor($farm->getSize() * Farm::MAX_ANIMALS_PER_HECTARE);
        $current = $this->cowRepository->count(['farm' => $farm, 'slaughter' => null]);

        if ($value->getId()) {
            $current--;
        }

        if ($current >= $limit) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ farm }}', $farm->getName())
                ->setParameter('{{ limit }}', (string) $limit)
                ->atPath('farm')
                ->addViolation();
        }
    }
}
