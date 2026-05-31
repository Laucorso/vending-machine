<?php

declare(strict_types=1);

namespace Tests\Unit\Money;

use VendingMachine\Domain\Money\ChangeCalculator;
use VendingMachine\Domain\Money\Coin;
use VendingMachine\Domain\Money\CoinBank;
use VendingMachine\Domain\Money\Exception\InsufficientChangeException;
use VendingMachine\Domain\Money\Money;
use PHPUnit\Framework\TestCase;

final class ChangeCalculatorTest extends TestCase
{
    private ChangeCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new ChangeCalculator();
    }

    public function test_zero_change_returns_no_coins(): void
    {
        $change = $this->calculator->calculate(Money::zero(), CoinBank::empty());

        self::assertTrue($change->isEmpty());
    }

    public function test_it_returns_the_fewest_coins_greedily(): void
    {
        $bank = CoinBank::fromCounts([100 => 5, 25 => 5, 10 => 5, 5 => 5]);

        $change = $this->calculator->calculate(Money::fromCents(35), $bank);

        self::assertSame([0.25, 0.10], $change->toDecimals());
    }

    public function test_it_respects_what_the_bank_actually_holds(): void
    {
        // No 25c available: must fall back to 10c + 10c + 10c + 5c.
        $bank = CoinBank::fromCounts([25 => 0, 10 => 3, 5 => 1]);

        $change = $this->calculator->calculate(Money::fromCents(35), $bank);

        self::assertSame([0.10, 0.10, 0.10, 0.05], $change->toDecimals());
    }

    public function test_it_throws_when_change_cannot_be_made(): void
    {
        $bank = CoinBank::fromCounts([100 => 10]); // only 1-euro coins

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
}
