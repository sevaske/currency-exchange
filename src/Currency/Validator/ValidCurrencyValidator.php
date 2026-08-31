<?php

namespace App\Currency\Validator;

use App\Currency\Exchange\FileExchangeRateProvider;
use Brick\Money\Currency;
use Brick\Money\Exception\UnknownCurrencyException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class ValidCurrencyValidator extends ConstraintValidator
{
    public function __construct(
        private readonly FileExchangeRateProvider $exchangeRateProvider,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidCurrency) {
            throw new UnexpectedTypeException($constraint, ValidCurrency::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $code = strtoupper($value);

        try {
            Currency::of($code);

            return;
        } catch (UnknownCurrencyException) {
            if ($this->exchangeRateProvider->hasCurrency($code)) {
                return;
            }
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation();
    }
}
