<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class UniqueAliveCode extends Constraint
{
    public string $message = 'Já existe um animal vivo com o código "{{ code }}".';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
