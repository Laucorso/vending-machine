<?php

declare(strict_types=1);

namespace Tests\Unit\Money;

use PHPUnit\Framework\TestCase;
use VendingMachine\Domain\Money\Enum\Coin;
use VendingMachine\Domain\Money\Exception\InvalidCoinException;

final class CoinTest extends TestCase
{
    public function test_it_builds_coins_from_decimal_input(): void
    {
        self::assertSame(Coin::FiveCents, Coin::fromDecimal(0.05));
        self::assertSame(Coin::TwentyFiveCents, Coin::fromDecimal('0.25'));
        self::assertSame(Coin::OneEuro, Coin::fromDecimal(1));
    }

    public function test_it_rejects_coins_the_machine_does_not_accept(): void
    {
        $this->expectException(InvalidCoinException::class);

        Coin::fromDecimal(0.50);
    }

    public function test_it_orders_denominations_descending(): void
    {
        self::assertSame(
            [Coin::OneEuro, Coin::TwentyFiveCents, Coin::TenCents, Coin::FiveCents],
            Coin::descending(),
        );
    }
}
