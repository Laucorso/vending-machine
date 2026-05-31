<?php

declare(strict_types=1);

namespace VendingMachine\Domain\Catalog;

use VendingMachine\Domain\Money\Money;

/** An item the machine can sell: its selector and price (the catalog entry). */
final readonly class Product
{
    public function __construct(
        public ProductSelector $selector,
        public Money $price,
    ) {
    }

    public function name(): string
    {
        return $this->selector->displayName();
    }
}
