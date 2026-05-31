<?php

declare(strict_types=1);

namespace VendingMachine\Domain\Money;

use VendingMachine\Domain\Money\Exception\InsufficientChangeException;

/**
 * Computes the coins to return for a given amount, constrained by what the
 * bank actually holds.
 *
 * Strategy: greedy, largest-coin-first. For the canonical denomination set
 * {5, 10, 25, 100} greedy is optimal when coins are unlimited. With limited
 * stock greedy can occasionally fail to find change that a fuller search
 * (dynamic programming) would find. That trade-off is deliberate for this
 * domain: the algorithm lives behind a single service, so swapping it for a
 * DP implementation later is a one-class change with no impact on callers.
 */
final class ChangeCalculator
{
    /**
     * @throws InsufficientChangeException when the bank cannot make the amount.
     */
    public function calculate(Money $amount, CoinBank $available): CoinCollection
    {
        $remaining = $amount->cents;
        $change = CoinCollection::empty();
        $taken = $available->copy();

        foreach (Coin::descending() as $coin) {
            while ($remaining >= $coin->value && $taken->quantityOf($coin) > 0) {
                $remaining -= $coin->value;
                $taken->withdraw(new CoinCollection($coin));
                $change = $change->add($coin);
            }
        }

        if ($remaining !== 0) {
            throw InsufficientChangeException::forAmount($amount);
        }

        return $change;
    }
}
