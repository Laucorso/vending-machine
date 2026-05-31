<?php

declare(strict_types=1);

namespace VendingMachine\Domain\Catalog;

use VendingMachine\Domain\Catalog\Exception\ProductNotFoundException;
use VendingMachine\Domain\Catalog\Exception\ProductOutOfStockException;

/**
 * How many units of each catalog product the machine currently holds.
 *
 * Mutable: part of the VendingMachine aggregate's state, refilled by the
 * service person and decremented on each sale.
 */
final class ProductInventory
{
    /** @var array<string, Product> selector value => product */
    private array $products;

    /** @var array<string, int> selector value => quantity */
    private array $quantities;

    /**
     * @param array<string, Product> $catalog        selector value => product
     * @param array<string, int>     $quantities     selector value => quantity
     */
    public function __construct(array $catalog, array $quantities = [])
    {
        $this->products = $catalog;
        $this->quantities = [];

        foreach (array_keys($catalog) as $selector) {
            $this->quantities[$selector] = max(0, $quantities[$selector] ?? 0);
        }
    }

    public function productFor(ProductSelector $selector): Product
    {
        return $this->products[$selector->value]
            ?? throw ProductNotFoundException::forSelector($selector->value);
    }

    public function quantityOf(ProductSelector $selector): int
    {
        return $this->quantities[$selector->value] ?? 0;
    }

    public function isInStock(ProductSelector $selector): bool
    {
        return $this->quantityOf($selector) > 0;
    }

    /**
     * @throws ProductOutOfStockException
     */
    public function dispenseOne(ProductSelector $selector): void
    {
        if (! $this->isInStock($selector)) {
            throw ProductOutOfStockException::forSelector($selector);
        }

        $this->quantities[$selector->value]--;
    }
}
