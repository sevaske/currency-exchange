<?php

namespace App\Currency\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class ConvertCurrencyRequest
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Assert\Type('numeric')]
    public string $amount;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 10)]
    public string $from;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 10)]
    public string $to = 'USD';
}
