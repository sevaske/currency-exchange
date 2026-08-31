<?php

namespace App\Currency\Exception;

use Psr\Log\LogLevel;
use Symfony\Component\HttpKernel\Attribute\WithLogLevel;

#[WithLogLevel(LogLevel::CRITICAL)]
class CurrencyStorageException extends CurrencyException
{
}
