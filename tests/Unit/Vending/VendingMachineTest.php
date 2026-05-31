<?php

declare(strict_types=1);

namespace Tests\Unit\Vending;

use VendingMachine\Domain\Catalog\Catalog;
use VendingMachine\Domain\Catalog\Exception\ProductOutOfStockException;
use VendingMachine\Domain\Catalog\ProductInventory;
use VendingMachine\Domain\Catalog\ProductSelector;
use VendingMachine\Domain\Money\ChangeCalculator;
use VendingMachine\Domain\Money\Coin;
use VendingMachine\Domain\Money\CoinBank;
use VendingMachine\Domain\Money\Exception\InsufficientChangeException;
use VendingMachine\Domain\Vending\Exception\InsufficientFundsException;
use VendingMachine\Domain\Vending\VendingMachine;
use PHPUnit\Framework\TestCase;

final class VendingMachineTest extends TestCase
{
    private ChangeCalculator $changeCalculator;

    protected function setUp(): void
    {
        $this->changeCalculator = new ChangeCalculator();
    }

    /** Spec example 1: 1, 0.25, 0.25, GET-SODA -> SODA (exact change). */
    public function test_buying_with_exact_change_dispenses_the_product_and_no_change(): void
    {
        $machine = $this->machineWith(
            products: [ProductSelector::Soda->value => 1],
            coins: [],
        );

        $machine->insertCoin(Coin::OneEuro);
        $machine->insertCoin(Coin::TwentyFiveCents);
        $machine->insertCoin(Coin::TwentyFiveCents);

        $outcome = $machine->vend(ProductSelector::Soda, $this->changeCalculator);

        self::assertSame('Soda', $outcome->product->name());
        self::assertTrue($outcome->change->isEmpty());
    }

    /** Spec example 2: 0.10, 0.10, RETURN-COIN -> 0.10, 0.10. */
    public function test_return_coin_gives_back_exactly_what_was_inserted(): void
    {
        $machine = VendingMachine::empty();

        $machine->insertCoin(Coin::TenCents);
        $machine->insertCoin(Coin::TenCents);

        $returned = $machine->returnInsertedCoins();

        self::assertSame([0.10, 0.10], $returned->toDecimals());
        self::assertTrue($machine->insertedAmount()->isZero());
    }

    /** Spec example 3: 1, GET-WATER -> WATER, 0.25, 0.10 (change of 0.35). */
    public function test_buying_without_exact_change_returns_the_difference(): void
    {
        $machine = $this->machineWith(
            products: [ProductSelector::Water->value => 1],
            coins: [25 => 1, 10 => 1],
        );

        $machine->insertCoin(Coin::OneEuro);

        $outcome = $machine->vend(ProductSelector::Water, $this->changeCalculator);

        self::assertSame('Water', $outcome->product->name());
        self::assertSame([0.25, 0.10], $outcome->change->toDecimals());
    }

    public function test_inserted_coins_become_available_as_change(): void
    {
        // Bank is empty, yet the coins the customer inserts can themselves
        // fund the change. Water 0.65 paid with 0.75 -> 0.10 change, and a
        // 0.10 coin is among the ones inserted.
        $machine = $this->machineWith(
            products: [ProductSelector::Water->value => 1],
            coins: [],
        );

        $machine->insertCoin(Coin::TwentyFiveCents);
        $machine->insertCoin(Coin::TwentyFiveCents);
        $machine->insertCoin(Coin::TenCents);
        $machine->insertCoin(Coin::TenCents);
        $machine->insertCoin(Coin::FiveCents); // total 0.75, water 0.65 -> change 0.10

        $outcome = $machine->vend(ProductSelector::Water, $this->changeCalculator);

        self::assertSame([0.10], $outcome->change->toDecimals());
    }

    public function test_it_refuses_to_sell_without_enough_money(): void
    {
        $machine = $this->machineWith([ProductSelector::Soda->value => 1], []);
        $machine->insertCoin(Coin::OneEuro);

        $this->expectException(InsufficientFundsException::class);

        $machine->vend(ProductSelector::Soda, $this->changeCalculator);
    }

    public function test_it_refuses_to_sell_a_sold_out_product(): void
    {
        $machine = $this->machineWith([ProductSelector::Soda->value => 0], []);
        $machine->insertCoin(Coin::OneEuro);
        $machine->insertCoin(Coin::OneEuro);

        $this->expectException(ProductOutOfStockException::class);

        $machine->vend(ProductSelector::Soda, $this->changeCalculator);
    }

    public function test_it_does_not_take_money_when_it_cannot_make_change(): void
    {
        // Water 0.65, pay with 1 euro -> needs 0.35 change but bank is empty.
        $machine = $this->machineWith([ProductSelector::Water->value => 1], []);
        $machine->insertCoin(Coin::OneEuro);

        try {
            $machine->vend(ProductSelector::Water, $this->changeCalculator);
            self::fail('Expected InsufficientChangeException.');
        } catch (InsufficientChangeException) {
            // The balance must be preserved so the customer can get a refund.
            self::assertSame(100, $machine->insertedAmount()->cents);
        }
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
