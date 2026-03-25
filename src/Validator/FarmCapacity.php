<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class FarmCapacity extends Constraint
{
    public string $message = 'A fazenda "{{ farm }}" atingiu o limite de {{ limit }} animais (máximo de 18 por hectare).';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
