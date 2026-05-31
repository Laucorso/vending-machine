<?php

declare(strict_types=1);

namespace VendingMachine\Application\Dto;

use VendingMachine\Domain\Money\CoinCollection;

/** Output of returning coins (RETURN-COIN): the coins handed back. */
final readonly class CoinReturn
{
    /** @param list<float> $coins */
    public function __construct(public array $coins)
    {
    }

    public static function from(CoinCollection $coins): self
    {
        return new self($coins->toDecimals());
    }
}
