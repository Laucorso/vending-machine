<?php

declare(strict_types=1);

namespace VendingMachine\Domain\Money\Exception;

use VendingMachine\Domain\ErrorCode;
use VendingMachine\Domain\VendingMachineException;

final class InvalidCoinException extends \DomainException implements VendingMachineException
{
    public static function forValue(int|float|string $value): self
    {
        return new self(sprintf('"%s" is not a coin this machine accepts.', (string) $value));
    }

    public function errorCode(): ErrorCode
    {
        return ErrorCode::InvalidCoin;
    }
}
