<?php

declare(strict_types=1);

namespace VendingMachine\Domain\Catalog\Exception;

use VendingMachine\Domain\Catalog\ProductSelector;
use VendingMachine\Domain\ErrorCode;
use VendingMachine\Domain\VendingMachineException;

final class ProductOutOfStockException extends \DomainException implements VendingMachineException
{
    public static function forSelector(ProductSelector $selector): self
    {
        return new self(sprintf('%s is sold out.', $selector->displayName()));
    }

    public function errorCode(): ErrorCode
    {
        return ErrorCode::OutOfStock;
    }
}
