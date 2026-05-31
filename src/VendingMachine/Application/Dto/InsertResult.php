<?php

declare(strict_types=1);

namespace VendingMachine\Application\Dto;

use VendingMachine\Domain\Money\Money;

/** Output of inserting a coin: the customer's running balance. */
final readonly class InsertResult
{
    public function __construct(public float $insertedTotal) {}

    public static function from(Money $insertedTotal): self
    {
        return new self($insertedTotal->toDecimal());
    }
}
