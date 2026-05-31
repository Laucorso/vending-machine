<?php

declare(strict_types=1);

namespace VendingMachine\Application\Request;

use VendingMachine\Domain\Catalog\Catalog;
use VendingMachine\Domain\Catalog\ProductInventory;
use VendingMachine\Domain\Money\CoinBank;

/**
 * Input boundary for a service visit: the new product counts and the new
 * change float the technician loads into the machine.
 *
 * It validates and assembles the domain objects (inventory + coin bank) so the
 * action stays a thin orchestrator and the domain never sees raw arrays.
 */
final readonly class ServiceRequest
{
    /**
     * @param array<string, int> $productCounts selector value => quantity
     * @param array<int, int>    $coinCounts    coin value (cents) => quantity
     */
    private function __construct(
        public array $productCounts,
        public array $coinCounts,
    ) {
    }

    /**
     * @param array<string, int> $productCounts selector value => quantity
     * @param array<int, int>    $coinCounts    coin value (cents) => quantity
     */
    public static function fromCounts(array $productCounts, array $coinCounts): self
    {
        return new self($productCounts, $coinCounts);
    }

    public function toInventory(): ProductInventory
    {
        return new ProductInventory(Catalog::default(), $this->productCounts);
    }

    public function toCoinBank(): CoinBank
    {
        return CoinBank::fromCounts($this->coinCounts);
    }
}
