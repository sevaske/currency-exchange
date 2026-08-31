<?php

namespace App\Tests\Unit\Currency\Validator;

use App\Currency\Exchange\FileExchangeRateProvider;
use App\Currency\Validator\ValidCurrency;
use App\Currency\Validator\ValidCurrencyValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class ValidCurrencyValidatorTest extends ConstraintValidatorTestCase
{
    private FileExchangeRateProvider $exchangeRateProvider;

    protected function createValidator(): ValidCurrencyValidator
    {
        $this->exchangeRateProvider = $this->createMock(FileExchangeRateProvider::class);

        return new ValidCurrencyValidator($this->exchangeRateProvider);
    }

    public function testIsoCurrencyCodeIsValid(): void
    {
        $this->exchangeRateProvider->expects($this->never())->method('hasCurrency');

        $this->validator->validate('EUR', new ValidCurrency());

        $this->assertNoViolation();
    }

    public function testIsoCurrencyCodeIsCaseInsensitive(): void
    {
        $this->exchangeRateProvider->expects($this->never())->method('hasCurrency');

        $this->validator->validate('eur', new ValidCurrency());

        $this->assertNoViolation();
    }

    public function testKnownCryptoCurrencyCodeIsValid(): void
    {
        $this->exchangeRateProvider->expects($this->once())
            ->method('hasCurrency')
            ->with('BTC')
            ->willReturn(true);

        $this->validator->validate('BTC', new ValidCurrency());

        $this->assertNoViolation();
    }

    public function testUnknownCurrencyCodeRaisesViolation(): void
    {
        $this->exchangeRateProvider->expects($this->once())
            ->method('hasCurrency')
            ->with('XYZ')
            ->willReturn(false);

        $constraint = new ValidCurrency();
        $this->validator->validate('XYZ', $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', 'XYZ')
            ->assertRaised();
    }

    public function testNullValueIsValid(): void
    {
        $this->exchangeRateProvider->expects($this->never())->method('hasCurrency');

        $this->validator->validate(null, new ValidCurrency());

        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid(): void
    {
        $this->exchangeRateProvider->expects($this->never())->method('hasCurrency');

        $this->validator->validate('', new ValidCurrency());

        $this->assertNoViolation();
    }

    public function testNonStringValueThrowsException(): void
    {
        $this->exchangeRateProvider->expects($this->never())->method('hasCurrency');

        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate(123, new ValidCurrency());
    }

    public function testUnexpectedConstraintTypeThrowsException(): void
    {
        $this->exchangeRateProvider->expects($this->never())->method('hasCurrency');

        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('EUR', new class extends \Symfony\Component\Validator\Constraint {});
    }
}
