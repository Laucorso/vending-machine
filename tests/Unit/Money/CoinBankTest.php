<?php

declare(strict_types=1);

namespace Tests\Unit\Money;

use PHPUnit\Framework\TestCase;
use VendingMachine\Domain\Money\Enum\Coin;
use VendingMachine\Domain\Money\CoinBank;
use VendingMachine\Domain\Money\CoinCollection;

final class CoinBankTest extends TestCase
{
    public function test_it_accumulates_multiple_deposits_of_the_same_coin(): void
    {
        $bank = CoinBank::empty();

        $bank->deposit(new CoinCollection(Coin::OneEuro));
        $bank->deposit(new CoinCollection(Coin::OneEuro));
        $bank->deposit(new CoinCollection(Coin::OneEuro));

        self::assertSame(3, $bank->quantityOf(Coin::OneEuro));
    }

    public function test_it_deposits_and_withdraws_coins(): void
    {
        $bank = CoinBank::empty();
        $bank->deposit(new CoinCollection(Coin::OneEuro, Coin::OneEuro));
        $bank->withdraw(new CoinCollection(Coin::OneEuro));

        self::assertSame(1, $bank->quantityOf(Coin::OneEuro));
    }

    public function test_a_copy_is_independent_of_the_original(): void
    {
        $bank = CoinBank::fromCounts([100 => 2]);
        $copy = $bank->copy();
        $copy->withdraw(new CoinCollection(Coin::OneEuro));

        self::assertSame(2, $bank->quantityOf(Coin::OneEuro));
        self::assertSame(1, $copy->quantityOf(Coin::OneEuro));
    }

    public function test_it_cannot_withdraw_coins_it_does_not_hold(): void
    {
        $this->expectException(\LogicException::class);

        CoinBank::empty()->withdraw(new CoinCollection(Coin::FiveCents));
    }

    public function test_it_can_withdraw_all_available_coins(): void
    {
        $bank = CoinBank::fromCounts([100 => 2]);

        $bank->withdraw(new CoinCollection(Coin::OneEuro, Coin::OneEuro));

        self::assertSame(0, $bank->quantityOf(Coin::OneEuro));
    }
}
