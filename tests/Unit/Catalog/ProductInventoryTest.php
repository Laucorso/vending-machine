<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog;

use PHPUnit\Framework\TestCase;
use VendingMachine\Domain\Catalog\Catalog;
use VendingMachine\Domain\Catalog\Exception\ProductOutOfStockException;
use VendingMachine\Domain\Catalog\ProductInventory;
use VendingMachine\Domain\Catalog\ProductSelector;

final class ProductInventoryTest extends TestCase
{
    public function test_it_reports_stock_and_prices_from_the_catalog(): void
    {
        $inventory = new ProductInventory(Catalog::default(), [ProductSelector::Water->value => 3]);

        self::assertSame(3, $inventory->quantityOf(ProductSelector::Water));
        self::assertSame(65, $inventory->productFor(ProductSelector::Water)->price->cents);
        self::assertTrue($inventory->isInStock(ProductSelector::Water));
        self::assertFalse($inventory->isInStock(ProductSelector::Soda));
    }

    public function test_it_decrements_stock_when_a_product_is_dispensed(): void
    {
        $inventory = new ProductInventory(Catalog::default(), [ProductSelector::Juice->value => 1]);

        $inventory->dispenseOne(ProductSelector::Juice);

        self::assertSame(0, $inventory->quantityOf(ProductSelector::Juice));
    }

    public function test_it_refuses_to_dispense_a_sold_out_product(): void
    {
        $inventory = new ProductInventory(Catalog::default(), [ProductSelector::Soda->value => 0]);

        $this->expectException(ProductOutOfStockException::class);

        $inventory->dispenseOne(ProductSelector::Soda);
    }
}
