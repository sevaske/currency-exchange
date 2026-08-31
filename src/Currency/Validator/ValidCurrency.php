<?php

namespace App\Currency\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidCurrency extends Constraint
{
    public string $message = 'Unknown currency code "{{ value }}".';
}
