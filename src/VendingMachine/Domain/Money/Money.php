<?php

declare(strict_types=1);

namespace VendingMachine\Domain\Money;

use InvalidArgumentException;

/**
 * An immutable monetary amount stored as a non-negative integer of cents.
 *
 * Keeping money in cents avoids every class of floating point rounding bug.
 */
final readonly class Money
{
    private function __construct(public int $cents)
    {
        if ($cents < 0) {
            throw new InvalidArgumentException('Money cannot be negative.');
        }
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function add(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function subtract(self $other): self
    {
        return new self($this->cents - $other->cents);
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    public function isGreaterThanOrEqualTo(self $other): bool
    {
        return $this->cents >= $other->cents;
    }

    public function isLessThan(self $other): bool
    {
        return $this->cents < $other->cents;
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents;
    }

    /** Human-facing decimal value, e.g. 65 cents -> 0.65. */
    public function toDecimal(): float
    {
        return $this->cents / 100;
    }
}
