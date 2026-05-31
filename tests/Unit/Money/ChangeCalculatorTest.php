<?php

declare(strict_types=1);

namespace Tests\Unit\Money;

use Tests\TestCase;
use VendingMachine\Domain\Money\ChangeCalculator;
use VendingMachine\Domain\Money\Coin;
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

    /**
     * Documents the known limitation of the greedy strategy: it can fail to
     * make change even when the available coins *could* form the amount.
     *
     * Amount: 0.30. Bank: one 0.25 and three 0.10.
     *   - Greedy takes the 0.25 first, then needs 0.05, has no 0.05, and 0.10
     *     no longer fits -> it gives up and throws.
     *   - But a valid solution exists: 0.10 + 0.10 + 0.10 = 0.30.
     *
     * The brute-force check below proves the solution exists, so this test is
     * unambiguously about greedy's blind spot, not about an impossible amount.
     * A dynamic-programming calculator would succeed here; swapping it in is a
     * single-class change because the strategy lives behind ChangeCalculator.
     */
    public function test_greedy_can_fail_on_change_a_full_search_would_find(): void
    {
        $amount = 30;
        $available = [25 => 1, 10 => 3, 5 => 0];

        self::assertTrue(
            $this->aSolutionExistsFor($amount, $available),
            'Pre-condition: the bank can form the amount with some combination.',
        );

        $this->expectException(InsufficientChangeException::class);

        $this->calculator->calculate(Money::fromCents($amount), CoinBank::fromCounts($available));
    }

    /**
     * Exhaustive feasibility check used only by the test above: can the given
     * coin stock form exactly $amount cents?
     *
     * @param  array<int, int>  $available  coin value (cents) => quantity
     */
    private function aSolutionExistsFor(int $amount, array $available): bool
    {
        if ($amount === 0) {
            return true;
        }

        foreach ($available as $value => $quantity) {
            if ($quantity > 0 && $value <= $amount) {
                $next = $available;
                $next[$value]--;
                if ($this->aSolutionExistsFor($amount - $value, $next)) {
                    return true;
                }
            }
        }

        return false;
    }
}
