<?php

namespace App\Currency\Request;

use App\Currency\Validator\ValidCurrency;
use Symfony\Component\Validator\Constraints as Assert;

final class CurrencyRatesRequest
{
    #[Assert\NotBlank]
    #[ValidCurrency]
    public string $base = 'USD';
}
