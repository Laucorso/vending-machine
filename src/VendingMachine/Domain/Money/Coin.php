<?php

declare(strict_types=1);

namespace VendingMachine\Domain\Money;

use VendingMachine\Domain\Money\Exception\InvalidCoinException;

/**
 * The set of physical coins the machine accepts and dispenses.
 *
 * Values are expressed in cents to keep all monetary arithmetic in the
 * integer domain (floating point is never used for money).
 */
enum Coin: int
{
    case FiveCents = 5;
    case TenCents = 10;
    case TwentyFiveCents = 25;
    case OneEuro = 100;

    /**
     * Build a coin from a human-facing decimal value (e.g. 0.25 or 1).
     *
     * @throws InvalidCoinException when the value is not an accepted coin.
     */
    public static function fromDecimal(int|float|string $value): self
    {
        $cents = (int) round(((float) $value) * 100);
        $coin = self::tryFrom($cents);

        if ($coin === null) {
            throw InvalidCoinException::forValue($value);
        }

        return $coin;
    }

    public function amount(): Money
    {
        return Money::fromCents($this->value);
    }

    /**
     * Denominations ordered from highest to lowest, the order a greedy
     * change algorithm needs to consume them.
     *
     * @return list<self>
     */
    public static function descending(): array
    {
        $coins = self::cases();
        usort($coins, static fn (self $a, self $b): int => $b->value <=> $a->value);

        return $coins;
    }
}
