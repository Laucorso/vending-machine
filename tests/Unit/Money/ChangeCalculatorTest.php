<?php

declare(strict_types=1);

namespace Tests\Unit\Money;

use PHPUnit\Framework\TestCase;
use VendingMachine\Domain\Money\ChangeCalculator;
use VendingMachine\Domain\Money\Enum\Coin;
use VendingMachine\Domain\Money\CoinBank;
use VendingMachine\Domain\Money\Exception\InsufficientChangeException;
use VendingMachine\Domain\Money\Money;

final class ChangeCalculatorTest extends TestCase
{
    private ChangeCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new ChangeCalculator;
    }

    public function test_zero_change_returns_no_coins(): void
    {
        $change = $this->calculator->calculate(Money::zero(), CoinBank::empty());

        self::assertTrue($change->isEmpty());
    }

    public function test_it_returns_a_valid_combination_of_coins(): void
    {
        $bank = CoinBank::fromCounts([100 => 5, 25 => 5, 10 => 5, 5 => 5]);

        $change = $this->calculator->calculate(Money::fromCents(35), $bank);

        self::assertSame(35, $change->total()->cents);
    }

    public function test_it_respects_coin_inventory_limits(): void
    {
        $bank = CoinBank::fromCounts([25 => 0, 10 => 3, 5 => 1]);

        $change = $this->calculator->calculate(Money::fromCents(35), $bank);

        self::assertSame(35, $change->total()->cents);
    }

    public function test_it_throws_when_no_solution_exists(): void
    {
        $bank = CoinBank::fromCounts([100 => 10]);

        $this->expectException(InsufficientChangeException::class);

        $this->calculator->calculate(Money::fromCents(35), $bank);
    }

    public function test_it_does_not_mutate_the_source_bank(): void
    {
        $bank = CoinBank::fromCounts([25 => 1, 10 => 1]);

        $this->calculator->calculate(Money::fromCents(35), $bank);

        self::assertSame(1, $bank->quantityOf(Coin::TwentyFiveCents));
        self::assertSame(1, $bank->quantityOf(Coin::TenCents));
    }

    public function test_it_finds_a_valid_solution_even_with_limited_stock(): void
    {
        $bank = CoinBank::fromCounts([
            25 => 1,
            10 => 3,
            5 => 0,
        ]);

        $change = $this->calculator->calculate(
            Money::fromCents(30),
            $bank,
        );

        self::assertSame(30, $change->total()->cents);
    }

    public function test_it_prefers_larger_coins_when_multiple_solutions_exist(): void
    {
        $bank = CoinBank::fromCounts([
            10 => 3,
            5 => 0,
        ]);

        $change = $this->calculator->calculate(
            Money::fromCents(30),
            $bank
        );

        self::assertSame([0.10, 0.10, 0.10], $change->toDecimals());
    }
}