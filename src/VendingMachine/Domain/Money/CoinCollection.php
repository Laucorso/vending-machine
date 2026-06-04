<?php

declare(strict_types=1);

namespace VendingMachine\Domain\Money;

use VendingMachine\Domain\Money\Enum\Coin;

/**
 * An immutable, ordered bag of coins.
 *
 * Used for two distinct concepts that happen to share the same shape:
 * the money a customer has inserted, and the coins handed back as change.
 */
final readonly class CoinCollection
{
    /** @var list<Coin> */
    private array $coins;

    public function __construct(Coin ...$coins)
    {
        $this->coins = array_values($coins);
    }

    public static function empty(): self
    {
        return new self;
    }

    public function add(Coin $coin): self
    {
        $coins = $this->coins;
        $coins[] = $coin;

        return new self(...$coins);
    }

    public function total(): Money
    {
        return array_reduce(
            $this->coins,
            static fn (Money $carry, Coin $coin): Money => $carry->add($coin->amount()),
            Money::zero(),
        );
    }

    public function isEmpty(): bool
    {
        return $this->coins === [];
    }

    /** @return list<Coin> */
    public function coins(): array
    {
        return $this->coins;
    }

    /**
     * Decimal values of each coin, for presentation/serialization.
     *
     * @return list<float>
     */
    public function toDecimals(): array
    {
        return array_map(static fn (Coin $coin): float => $coin->amount()->toDecimal(), $this->coins);
    }
}
