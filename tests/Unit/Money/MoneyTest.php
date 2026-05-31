<?php

declare(strict_types=1);

namespace Tests\Unit\Money;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use VendingMachine\Domain\Money\Money;

final class MoneyTest extends TestCase
{
    public function test_it_adds_and_subtracts_in_cents(): void
    {
        $result = Money::fromCents(65)->add(Money::fromCents(35));

        self::assertSame(100, $result->cents);
        self::assertSame(35, Money::fromCents(100)->subtract(Money::fromCents(65))->cents);
    }

    public function test_it_compares_amounts(): void
    {
        self::assertTrue(Money::fromCents(150)->isGreaterThanOrEqualTo(Money::fromCents(150)));
        self::assertTrue(Money::fromCents(50)->isLessThan(Money::fromCents(65)));
        self::assertTrue(Money::fromCents(0)->isZero());
        self::assertTrue(Money::fromCents(65)->equals(Money::fromCents(65)));
    }

    public function test_it_exposes_a_decimal_representation(): void
    {
        self::assertSame(0.65, Money::fromCents(65)->toDecimal());
    }

    public function test_it_rejects_negative_amounts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromCents(10)->subtract(Money::fromCents(25));
    }
}
