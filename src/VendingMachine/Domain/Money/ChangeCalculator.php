<?php

declare(strict_types=1);

namespace VendingMachine\Domain\Money;

use VendingMachine\Domain\Money\Enum\Coin;
use VendingMachine\Domain\Money\Exception\InsufficientChangeException;

final class ChangeCalculator
{
    public function calculate(Money $amount, CoinBank $bank): CoinCollection
    {
        $available = $this->bankToCounters($bank);

        $result = $this->solve($amount->cents, $available, Coin::descending());

        if ($result === null) {
            throw InsufficientChangeException::forAmount($amount);
        }

        return new CoinCollection(...$result);
    }

    /**
     * @param  Coin[] $coins
     * @param  array<int, int> $available [coinValueCents => quantity]
     * @return Coin[]|null
     */
    private function solve(int $remaining, array $available, array $coins, int $i = 0): ?array
    {
        if ($remaining === 0) {
            return [];
        }

        if (!isset($coins[$i])) {
            return null;
        }

        $coin = $coins[$i];
        $value = $coin->value;
        $max = min(
            intdiv($remaining, $value),
            $available[$value] ?? 0,
        );

        // Try largest quantity first (greedy-first heuristic within backtracking)
        for ($use = $max; $use >= 0; $use--) {
            $next = $available;
            $next[$value] -= $use;

            $result = $this->solve(
                    $remaining - ($use * $value), 
                    $next, 
                    $coins, 
                    $i + 1
                );

            if ($result !== null) {
                return array_merge(array_fill(0, $use, $coin), $result);
            }
        }

        return null;
    }

    /**
     * @return array<int, int>  [coinValueCents => quantity]
     */
    private function bankToCounters(CoinBank $bank): array
    {
        $counters = [];

        foreach (Coin::descending() as $coin) {
            $counters[$coin->value] = $bank->quantityOf($coin);
        }

        return $counters;
    }
}