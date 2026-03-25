<?php

namespace App\Validator;

use App\Entity\Cow;
use App\Repository\CowRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UniqueAliveCodeValidator extends ConstraintValidator
{
    public function __construct(
        private CowRepository $cowRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueAliveCode) {
            throw new UnexpectedTypeException($constraint, UniqueAliveCode::class);
        }

        if (!$value instanceof Cow) {
            throw new UnexpectedValueException($value, Cow::class);
        }

        if (!$value->getCode()) {
            return;
        }

        $existing = $this->cowRepository->findOneAliveByCodeExcluding(
            $value->getCode(),
            $value->getId()
        );

        if ($existing) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ code }}', $value->getCode())
                ->atPath('code')
                ->addViolation();
        }
    }
}
