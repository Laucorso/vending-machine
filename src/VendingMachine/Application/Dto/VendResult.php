<?php

declare(strict_types=1);

namespace VendingMachine\Application\Dto;

use VendingMachine\Domain\Vending\VendOutcome;

/** Output of a successful sale: the product name and the change coins. */
final readonly class VendResult
{
    /** @param list<float> $change */
    public function __construct(
        public string $product,
        public array $change,
    ) {}

    public static function from(VendOutcome $outcome): self
    {
        return new self(
            $outcome->product->name(),
            $outcome->change->toDecimals(),
        );
    }
}
