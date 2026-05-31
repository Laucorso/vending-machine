<?php

declare(strict_types=1);

namespace VendingMachine\Domain\Money;

/**
 * The float of coins the machine holds and can dispense as change.
 *
 * Mutable on purpose: it is part of the VendingMachine aggregate's internal
 * state and only ever changes through the aggregate's own operations.
 */
final class CoinBank
{
    /** @var array<int, int> coin value (cents) => quantity */
    private array $quantities;

    /**
     * @param array<int, int> $quantities coin value (cents) => quantity
     */
    private function __construct(array $quantities)
    {
        $this->quantities = $quantities;
    }

    public static function empty(): self
    {
        $quantities = [];
        foreach (Coin::cases() as $coin) {
            $quantities[$coin->value] = 0;
        }

        return new self($quantities);
    }

    /**
     * @param array<int, int> $countsByCoinValue coin value (cents) => quantity
     */
    public static function fromCounts(array $countsByCoinValue): self
    {
        $bank = self::empty();
        foreach ($countsByCoinValue as $coinValue => $quantity) {
            $bank->set(Coin::from($coinValue), $quantity);
        }

        return $bank;
    }

    public function set(Coin $coin, int $quantity): void
    {
        if ($quantity < 0) {
            throw new \InvalidArgumentException('Coin quantity cannot be negative.');
        }

        $this->quantities[$coin->value] = $quantity;
    }

    public function add(Coin $coin): void
    {
        $this->quantities[$coin->value]++;
    }

    public function deposit(CoinCollection $coins): void
    {
        foreach ($coins->coins() as $coin) {
            $this->add($coin);
        }
    }

    public function withdraw(CoinCollection $coins): void
    {
        foreach ($coins->coins() as $coin) {
            if ($this->quantities[$coin->value] <= 0) {
                throw new \LogicException('Cannot withdraw a coin the bank does not hold.');
            }
            $this->quantities[$coin->value]--;
        }
    }

    public function quantityOf(Coin $coin): int
    {
        return $this->quantities[$coin->value];
    }

    /** A deep copy, safe to use for "what-if" change projections. */
    public function copy(): self
    {
        return new self($this->quantities);
    }
}
