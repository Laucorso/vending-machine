<?php

declare(strict_types=1);

namespace VendingMachine\Domain\Vending;

use VendingMachine\Domain\Catalog\Product;
use VendingMachine\Domain\Money\CoinCollection;

/** The result of a successful sale: the product and the change returned. */
final readonly class VendOutcome
{
    public function __construct(
        public Product $product,
        public CoinCollection $change,
    ) {
    }
}
