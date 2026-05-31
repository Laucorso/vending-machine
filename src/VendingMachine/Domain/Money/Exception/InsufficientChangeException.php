<?php

declare(strict_types=1);

namespace VendingMachine\Domain\Money\Exception;

use VendingMachine\Domain\ErrorCode;
use VendingMachine\Domain\Money\Money;
use VendingMachine\Domain\VendingMachineException;

final class InsufficientChangeException extends \DomainException implements VendingMachineException
{
    public static function forAmount(Money $amount): self
    {
        return new self(sprintf('The machine cannot return %.2f in change.', $amount->toDecimal()));
    }

    public function errorCode(): ErrorCode
    {
        return ErrorCode::InsufficientChange;
    }
}
