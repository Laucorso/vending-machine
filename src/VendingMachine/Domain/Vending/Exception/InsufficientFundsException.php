<?php

declare(strict_types=1);

namespace VendingMachine\Domain\Vending\Exception;

use VendingMachine\Domain\Catalog\Product;
use VendingMachine\Domain\Money\Money;
use VendingMachine\Domain\VendingMachineException;

final class InsufficientFundsException extends \DomainException implements VendingMachineException
{
    public static function needMoreFor(Product $product, Money $inserted): self
    {
        return new self(sprintf(
            '%s costs %.2f but only %.2f was inserted.',
            $product->name(),
            $product->price->toDecimal(),
            $inserted->toDecimal(),
        ));
    }
}
