<?php

declare(strict_types=1);

namespace VendingMachine\Domain\Catalog\Exception;

use VendingMachine\Domain\ErrorCode;
use VendingMachine\Domain\VendingMachineException;

final class ProductNotFoundException extends \DomainException implements VendingMachineException
{
    public static function forSelector(string $selector): self
    {
        return new self(sprintf('No product is mapped to selector "%s".', $selector));
    }

    public function errorCode(): ErrorCode
    {
        return ErrorCode::ProductNotFound;
    }
}
