<?php

declare(strict_types=1);

namespace Tests\Integration\Vending;

use PHPUnit\Framework\TestCase;
use VendingMachine\Domain\Catalog\Catalog;
use VendingMachine\Domain\Catalog\ProductInventory;
use VendingMachine\Domain\Catalog\ProductSelector;
use VendingMachine\Domain\Money\ChangeCalculator;
use VendingMachine\Domain\Money\Enum\Coin;
use VendingMachine\Domain\Money\CoinBank;
use VendingMachine\Domain\Vending\VendingMachine;

final class VendingMachineJourneyTest extends TestCase
{
    private ChangeCalculator $changeCalculator;

    protected function setUp(): void
    {
        $this->changeCalculator = new ChangeCalculator();
    }

    public function test_buy_soda_with_exact_change(): void
    {
        $machine = $this->machineWith(
            products: [ProductSelector::Soda->value => 1],
            coins:    [25 => 2, 10 => 2],
        );

        $bankBefore = $machine->availableChange()->cents;

        $machine->insertCoin(Coin::OneEuro);
        $machine->insertCoin(Coin::TwentyFiveCents);
        $machine->insertCoin(Coin::TwentyFiveCents);

        self::assertSame(150, $machine->insertedAmount()->cents);

        $outcome = $machine->vend(ProductSelector::Soda, $this->changeCalculator);

        self::assertSame('Soda', $outcome->product->name());
        self::assertSame(0, $outcome->change->total()->cents);
        self::assertTrue($machine->insertedAmount()->isZero());
        self::assertSame($bankBefore + 150, $machine->availableChange()->cents);
    }

    public function test_buy_water_receiving_correct_change(): void
    {
        $machine = $this->machineWith(
            products: [ProductSelector::Water->value => 1],
            coins: [25 => 1, 10 => 1],
        );

        $bankBefore = $machine->availableChange()->cents;

        $machine->insertCoin(Coin::OneEuro);

        $outcome = $machine->vend(ProductSelector::Water, $this->changeCalculator);

        self::assertSame('Water', $outcome->product->name());
        self::assertSame(35, $outcome->change->total()->cents);
        self::assertTrue($machine->insertedAmount()->isZero());
        self::assertSame($bankBefore + 100 - 35, $machine->availableChange()->cents);
    }

    /**
     * @param array<string, int> $products
     * @param array<int, int>    $coins
     */
    private function machineWith(array $products, array $coins): VendingMachine
    {
        $machine = VendingMachine::empty();
        $machine->service(
            new ProductInventory(Catalog::default(), $products),
            CoinBank::fromCounts($coins),
        );

        return $machine;
    }
}