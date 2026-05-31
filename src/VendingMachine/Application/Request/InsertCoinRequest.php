<?php

declare(strict_types=1);

namespace VendingMachine\Application\Request;

use VendingMachine\Domain\Money\Coin;

/**
 * Input boundary for "insert a coin". Translates untyped external input
 * (e.g. "0.25" from an HTTP form or a hardware coin sensor) into a validated
 * domain Coin, so the rest of the system only ever deals with valid values.
 */
final readonly class InsertCoinRequest
{
    private function __construct(public Coin $coin) {}

    public static function fromValue(int|float|string $value): self
    {
        return new self(Coin::fromDecimal($value));
    }
}
