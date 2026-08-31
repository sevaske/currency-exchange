<?php

namespace App\Currency\Request;

use App\Currency\Validator\ValidCurrency;
use Symfony\Component\Validator\Constraints as Assert;

final class ConvertCurrencyRequest
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Assert\Type('numeric')]
    public string $amount;

    #[Assert\NotBlank]
    #[ValidCurrency]
    public string $from;

    #[Assert\NotBlank]
    #[ValidCurrency]
    public string $to = 'USD';
}
