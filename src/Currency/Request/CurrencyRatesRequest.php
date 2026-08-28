<?php

namespace App\Currency\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CurrencyRatesRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 10)]
    public string $base = 'USD';
}
